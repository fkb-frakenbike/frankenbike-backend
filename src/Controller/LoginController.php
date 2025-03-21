<?php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\Routing\Annotation\Route;

    final class LoginController extends AbstractController
    {
        #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
        public function index(AuthenticationUtils $authenticationUtils): JsonResponse
        {
            // Récupérer l'erreur de connexion s'il y en a une
            $error = $authenticationUtils->getLastAuthenticationError();
            // Dernier nom d'utilisateur saisi par l'utilisateur
            $lastUsername = $authenticationUtils->getLastUsername();
        
            return new JsonResponse([
                'last_username' => $lastUsername,
                'error' => $error ? $error->getMessageKey() : null,
            ]);
        }
        
    }
