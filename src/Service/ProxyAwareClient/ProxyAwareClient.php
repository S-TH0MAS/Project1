<?php

namespace App\Service\ProxyAwareClient;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class ProxyAwareClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(default::HTTP_PROXY)%')]
        private ?string             $proxy = null
    )
    {
    }

    /**
     * Envoie une requête JSON.
     *
     * @param string $endpoint L'URL cible
     * @param array $body Les données à envoyer en JSON
     * @param string $method La méthode HTTP (POST par défaut)
     * @return array La réponse décodée
     *
     * @throws Exception En cas d'erreur (réseau, api, parsing)
     */
    public function send(string $endpoint, array $body, string $method = 'POST'): array
    {
        $httpMethod = strtoupper($method);

        $options = [
            'json' => $body,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        if (!empty($this->proxy)) {
            $options['proxy'] = $this->proxy;
        }

        // On initialise la réponse à null par sécurité
        $response = null;

        try {
            $response = $this->httpClient->request($httpMethod, $endpoint, $options);

            // Tente de convertir le JSON en tableau
            return $response->toArray();

        } catch (DecodingExceptionInterface $e) {
            // ERREUR JSON (Le serveur a répondu, mais pas du JSON)
            // On vérifie si $response existe avant de l'utiliser
            $rawContent = ($response) ? $response->getContent(false) : 'Pas de contenu';

            throw new Exception(
                sprintf('[JSON ERROR] Impossible de décoder la réponse. Contenu reçu : "%s"', substr($rawContent, 0, 500)),
                0,
                $e
            );

        } catch (TransportExceptionInterface $e) {
            // ERREUR RÉSEAU
            throw new Exception(
                sprintf('[NETWORK ERROR] Problème de connexion : %s', $e->getMessage()),
                0,
                $e
            );

        } catch (RedirectionExceptionInterface $e) {
            // ERREUR REDIRECTION (3xx) - Ajouté pour être complet
            throw new Exception(
                sprintf('[REDIRECT ERROR] Trop de redirections ou erreur 3xx : %s', $e->getMessage()),
                0,
                $e
            );

        } catch (ClientExceptionInterface $e) {
            // ERREUR CLIENT (4xx)
            throw new Exception(
                sprintf('[API CLIENT ERROR] Le serveur a rejeté la requête (4xx) : %s', $e->getMessage()),
                0,
                $e
            );

        } catch (ServerExceptionInterface $e) {
            // ERREUR SERVEUR (5xx)
            throw new Exception(
                sprintf('[API SERVER ERROR] Le serveur distant a planté (5xx) : %s', $e->getMessage()),
                0,
                $e
            );

        } catch (Throwable $e) {
            // AUTRES ERREURS
            throw new Exception(
                sprintf('[UNKNOWN ERROR] Une erreur inattendue est survenue : %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}