<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{

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

        // Étape 1: Récupérer l'utilisateur
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }
        // Étape 2: Vérifier le mot de passe
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        // Étape 3: Générer le JWT
        $token = $jwtManager->create($user);

        // Étape 4: Mettre le JWT dans un cookie sécurisé
        $response = new JsonResponse(['token' => $token]); //Token
        // $response->headers->setCookie(
        //     Cookie::create('AUTH_TOKEN', $token)
        //         ->withHttpOnly(true)
        //         ->withSecure(true) // Important en prod !
        //         ->withSameSite('Lax')
        //         ->withPath('/')
        //         ->withExpires(time() + 60 * 60 * 24 * 30)
        // );
        return $response;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(){
    }
}
