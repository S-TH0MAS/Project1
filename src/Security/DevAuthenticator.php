<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class DevAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        #[Autowire('%env(bool:DISABLE_JWT_AUTH)%')] private bool $disableJwt,
        #[Autowire('%env(string:TEST_USER_EMAIL)%')] private string $testUserEmail,
        #[Autowire('%kernel.environment%')] private string $env // <--- JUSTE ICI
    ) {
    }

    /**
     * Cette méthode décide si cet authenticator doit s'activer.
     */
    public function supports(Request $request): ?bool
    {
        // On s'active UNIQUEMENT si :
        // 1. On est en environnement 'dev'
        // 2. La variable DISABLE_JWT_AUTH est vraie
        // 3. L'URL commence par /api

        return $this->env === 'dev'
            && $this->disableJwt
            && str_starts_with($request->getPathInfo(), '/api');
    }

    public function authenticate(Request $request): Passport
    {
        // On crée un passeport pour l'utilisateur de test automatiquement
        return new SelfValidatingPassport(
            new UserBadge($this->testUserEmail)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // On laisse la requête continuer vers le contrôleur (null = continue)
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Dev authentication failed'], Response::HTTP_UNAUTHORIZED);
    }
}