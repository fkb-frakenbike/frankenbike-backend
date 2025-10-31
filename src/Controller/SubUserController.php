<?php

declare(strict_types=1);

namespace App\Controller;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SubUserController extends UserController
{
    public function __construct( private EntityManagerInterface $em, SerializerInterface $serializer)
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
    $projectsJson = $serializer->serialize($projects, 'json', ['groups' => ['project:list']]);

    return new JsonResponse([
        'user' => $userData,
        'projects' => json_decode($projectsJson),
    ], JsonResponse::HTTP_OK);
}

}
