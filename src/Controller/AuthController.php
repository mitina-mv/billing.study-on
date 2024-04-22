<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class AuthController extends AbstractController
{
    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth() : void
    {
    }
}
