<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
/**
 * This class is here for reference of authenticate to be
 * able to deny access unless granted.. to a certain method
 * or html element
 */
class AdminController extends AbstractController
{
    #[Route('/admin')]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new JsonResponse("hello world");
    }
}
