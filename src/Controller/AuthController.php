<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['message' => 'pong'], Response::HTTP_OK);
    }

    // Login is handled by json_login (security.yaml, route api_login).

    // Never reached: the api firewall intercepts this route (security.yaml, logout.path) and
    // AuthCookieSubscriber answers. The route only has to exist.
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Handled by the logout listener of the api firewall.');
    }
}
