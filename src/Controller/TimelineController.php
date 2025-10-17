<?php

namespace App\Controller;

use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TimelineController extends AbstractController
{
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    #[Route('/api/timelines/{userId}', name: 'user_timeline', methods: ['GET'])]
    public function userTimeline(int $userId): JsonResponse
    {
        $currentUser = $this->getUser();

        // Vérification utilisateur connecté
        if (!$currentUser instanceof UserInterface) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérification que l'utilisateur demandant est bien le même
        if ($currentUser->getId() !== $userId) {
            return new JsonResponse(['error' => 'Forbidden'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Récupération des projets du user
        $projects = $this->em->getRepository(Project::class)->findBy(['user' => $userId]);

        // Sérialisation via le serializer avec les groupes
        $json = $this->serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }
}
