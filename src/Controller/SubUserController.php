<?php

declare(strict_types=1);

namespace App\Controller;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class SubUserController extends UserController
{
    public function __construct( private EntityManagerInterface $em, SerializerInterface $serializer, private LoggerInterface $logger)
    {}

    #[Route('/api/me', name: 'me', methods: ['GET'])]
public function apiMe(SerializerInterface $serializer): JsonResponse
{
    $currentUser = $this->getUser();
    if (!$currentUser) {
        return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
    }

    $userData = $serializer->normalize($currentUser, null, ['groups' => ['user:read']]);

    $projects = $this->em->getRepository(Project::class)->findBy(['user' => $currentUser], ['createdAt' => 'DESC']);
    $this->logger->info('User projects retrieved', ['projects' => $projects]);
    $projectsJson = $serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

    return new JsonResponse([
        'user' => $userData,
        'projects' => json_decode($projectsJson),
    ], JsonResponse::HTTP_OK);
}

}
