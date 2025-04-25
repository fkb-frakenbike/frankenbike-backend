<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class AuthController extends AbstractController
{

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        // This should never be reached.
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(){
    }
}
