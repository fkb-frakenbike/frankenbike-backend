<?php

namespace App\Controller;

use App\Entity\Project;
use App\Service\ComponentPhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TimelineController extends AbstractController
{
    #[Route('/api/timelines/{userId}', name: 'user_timeline', methods: ['GET'])]
    public function userTimeline(
        int $userId,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ComponentPhotoStorage $componentPhotoStorage
    ): JsonResponse {
        // 1) Authorization
        $currentUser = $this->getUser();
        if (!$currentUser instanceof UserInterface) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($currentUser->getId() !== $userId /* && !$this->isGranted('ROLE_ADMIN') */) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 2) Fetch user projects
        $projects = $em->getRepository(Project::class)->findBy(['user' => $userId]);

        // 3) Build data manually to match frontend's expected shape
        $timelineData = [];

        foreach ($projects as $project) {
            $componentsArr = [];

            foreach ($project->getComponents() as $component) {
                $photoKey = $component->getPhotoS3Key();
                $photoUrl = $photoKey !== ''
                    ? $componentPhotoStorage->publicUrl($photoKey)
                    : null;

                $componentsArr[] = [
                    'id'          => $component->getId(),
                    'name'        => $component->getName(),
                    'description' => $component->getDescription(),
                    'category'    => $component->getCategory()->value,
                    'origin'      => $component->getOrigin()->value,
                    'img'         => $photoUrl,
                    // optional fields for future improvements:
                    // 'likes'    => $project->getLikes()->count(),
                    // 'comments' => $project->getComments()->count(),
                    // 'userImg'  => $project->getUser()->getProfile()?->getPhotoUrl(),
                    // 'userName' => $project->getUser()->getProfile()?->getNickname()
                    //             ?? $project->getUser()->getEmail(),
                    // 'nature'   => ...
                ];
            }

            $timelineData[] = [
                'projectId'   => $project->getId(),
                'projectName' => $project->getTitle(),
                'components'  => $componentsArr,
            ];
        }

        // 4) Serialize (no groups because we serialize an array, not entities)
        $json = $serializer->serialize($timelineData, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
