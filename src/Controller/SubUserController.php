<?php

declare(strict_types=1);

namespace App\Controller;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SubUserController extends UserController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        LoggerInterface $logger

    ) {
        parent::__construct($logger);
    }

    #[Route('/api/me', name: 'me', methods: ['GET'])]
    public function apiMe(): JsonResponse
    {
        $user = $this->getUser();
        $projects = $this->em->getRepository(Project::class)->findBy(['user' => $user]);

        $projectsJson = $this->serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

        return new JsonResponse($projectsJson,JsonResponse::HTTP_OK, [], true);
    }

}
