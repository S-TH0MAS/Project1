<?php
// src/Controller/ReactController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReactController extends AbstractController
{
    #[Route('/{reactRouting}', name: 'app_react_app', requirements: ['reactRouting' => '^(?!api|_(profiler|wdt)).+'], defaults: ['reactRouting' => null], priority: -1)]
    public function index(): Response
    {
        // 1. On récupère le chemin absolu vers le fichier index.html
        $projectDir = $this->getParameter('kernel.project_dir');
        $indexPath = $projectDir . '/public/index.html';

        // 2. Sécurité : On vérifie si le fichier existe (au cas où le build a échoué)
        if (!file_exists($indexPath)) {
            return new Response('Fichier index.html introuvable. Avez-vous lancé "npm run build" ?', 404);
        }

        // 3. On lit le contenu et on le renvoie comme une réponse HTML normale
        return new Response(file_get_contents($indexPath));
    }
}