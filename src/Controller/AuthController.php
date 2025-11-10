<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['message' => 'pong'], Response::HTTP_OK);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $rememberMe = $data['rememberMe'] ?? false;

        // Step 1: recover the user
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }
        // Step 2: Verify the password
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if($rememberMe) {
            $now = new \DateTimeImmutable();
            $later = $now->modify('+30 days');
            $payload = [
                'exp' => $later->getTimestamp(),
            ];
            $jwt = $jwtManager->create($user, $payload);
        } else {
            $jwt = $jwtManager->create($user);
        }

        $response = new JsonResponse(['message' => 'Connected'], Response::HTTP_OK);

        $isProd   = ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev') === 'prod';
        // 👉 For cross-site SPA → API, we need SameSite=None; Secure in prod
        $sameSite = $isProd ? 'none' : 'lax';
        $secure   = $isProd;

        $ttl       = $rememberMe ? 60 * 60 * 24 * 30 : 60 * 60; // 30d vs 1h
        $expiresAt = time() + $ttl;

        $cookie = Cookie::create('AUTH_TOKEN_COOKIE', $jwt)
            ->withHttpOnly(true)
            ->withSecure($secure)
            ->withSameSite($sameSite)
            ->withPath('/')
            ->withExpires($expiresAt);

        $response->headers->setCookie($cookie);
        return $response;
    }

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
