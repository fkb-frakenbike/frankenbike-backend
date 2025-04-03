<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!$email || !$plainPassword) {
            return new JsonResponse(['error' => 'Missing email or password'], 400);
        }

        // Find user by email
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Invalid email or password?
        if (!$user || !$passwordHasher->isPasswordValid($user, $plainPassword)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Create JWT token
        $token = $jwtManager->create($user);

        // Return it in JSON
        return new JsonResponse(['token' => $token], 200);
    }
}
