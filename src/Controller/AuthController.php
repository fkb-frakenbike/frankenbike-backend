<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
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

    // S2 replaces this with a real logout (session + remember-me series); until then it only
    // clears the obsolete JWT cookie.
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $isProd   = ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev') === 'prod';
        $sameSite = $isProd ? 'none' : 'lax';
        $secure   = $isProd;

        $expired = Cookie::create('AUTH_TOKEN_COOKIE', '')
            ->withHttpOnly(true)
            ->withSecure($secure)  // keep settings consistent
            ->withSameSite($sameSite)
            ->withPath('/')
            ->withExpires(1)    // 1 second after 1970 => removed by browser
        ;

        $response = new JsonResponse(['message' => 'Logged out']);
        $response->headers->setCookie($expired);

        return $response;
    }
}
