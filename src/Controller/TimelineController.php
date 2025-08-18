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

        if (!$currentUser instanceof UserInterface) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if ($currentUser->getId() !== $userId) {
            return new JsonResponse(['error' => 'Forbidden'], JsonResponse::HTTP_FORBIDDEN);
        }

        $projects = $this->em->getRepository(Project::class)->findBy(['user' => $userId]);

        $timelineData = [];
        foreach ($projects as $project) {
            $timelineData[] = [
                'title' => $project->getTitle(),
                'text' => $project->getDescription(),
                'img' => $project->getImageUrl(),
                'likes' => $project->getLikes()->count(),
                'comments' => $project->getComments()->count(),
                'userImg' => null,
                'userName' => null,
                'nature' => 'project',
                'variant' => 'purpleCard',
                'projectName' => $project->getTitle(),
            ];
        }

        return new JsonResponse($timelineData, JsonResponse::HTTP_OK);
    }
}
