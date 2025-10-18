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
        $user = $serializer->normalize($this->getUser(), null, ['groups' => ['user:read']]);
        $projects = $this->em->getRepository(Project::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $projectsArray = $serializer->normalize($projects, null, ['groups' => ['project:read']]);
        return $this->json([
            'user' => $user,
            'projects' => $projectsArray,
            'page' => 1,
            'limit' => count($projectsArray),
            'total' => count($projectsArray),
            'hasMore' => false
        ], JsonResponse::HTTP_OK);
    }

}
