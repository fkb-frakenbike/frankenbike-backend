<?php

namespace App\Controller;

use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class TimelineController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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

        $timelineData = [];

        foreach ($projects as $project) {
            $componentsArr = [];
            foreach ($project->getComponents() as $component) {
                $componentsArr[] = [
                    'id' => $component->getId(),
                    'name' => $component->getName(),
                    'description' => $component->getDescription(),
                    'category' => $component->getCategory()->value,
                    'origin' => $component->getOrigin()->value,
                    // Ajoute ici d'autres champs utiles pour ton frontend, ex: image, dates...
                ];
            }

            $timelineData[] = [
                'projectId' => $project->getId(),
                'projectName' => $project->getTitle(),
                'components' => $componentsArr,
            ];
        }

        return new JsonResponse($timelineData, JsonResponse::HTTP_OK);
    }
}
