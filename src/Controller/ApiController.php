<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): JsonResponse
    {
        throw new \Exception('Code de test pour le login API. Cette méthode ne doit pas être appelée directement.');
    }

    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        if(!$this->getUser()) {
            return $this->json(['message' => 'Accès refusé. Authentification requise.'], 401);
        }

        return $this->json(['message' => 'Votre api est opérationnelle ! vous êtes connecté en tant que : ' . $this->getUser()->getUserIdentifier()]);
    }

}