<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SubUserController extends UserController
{
    #[Route('/api/me', name: 'me', methods: ['GET'])]
    public function apiMe(): JsonResponse
    {
        // return $this->json($this->getUser());
        return $this->json($this->getUser());
    }
}
