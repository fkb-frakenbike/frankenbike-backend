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
    public function apiMe(): JsonResponse
    {
        $user = $this->getUser();
        $projects = $this->em->getRepository(Project::class)->findBy(['user' => $user]);

        $projectsJson = $serializer->serialize($projects, 'json');
//        $projectData = [];
//        foreach ($projects as $project) {
//            $projectData[] = [
//                'id' => $project->getId(),
//                'title' => $project->getTitle(),
//                'description' => $project->getDescription(),
//                'imageUrl' => $project->getImageUrl(),
//                'createdAt' => $project->getCreatedAt()?->format('c'),
//                'updatedAt' => $project->getUpdatedAt()?->format('c'),
//            ];
//        }

        # return $this->json($this->getUser());
//        return new JsonResponse([
//            'id' => $user->getId(),
//            'email' => $user->getEmail(),
//            'roles' => $user->getRoles(),
//            'createdAt' => $user->getCreatedAt()?->format('c'),
//            'projects' => $projectData, // Optional, or you can fill with IDs or names if needed
//            'likes' => [],    // Same
//            'userIdentifier' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
//        ]);
        return new JsonResponse($projectsJson,JsonResponse::HTTP_OK, [], true);
    }

}
