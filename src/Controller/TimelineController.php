<?php

namespace App\Controller;

use App\Entity\Component;
use App\Entity\Project;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TimelineController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, private SerializerInterface $serializer, private LoggerInterface $logger)
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
        $components = $this->em->getRepository(Component::class)
            ->createQueryBuilder('c')
            ->innerJoin('c.project', 'p')
            ->andWhere('p.user = :user')              // join through project → user
            ->setParameter('user', $currentUser)      // or ->setParameter('user', $userId) with an equality on ID (see variant below)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()->getResult();

        $timelineData = [];

//        foreach ($projects as $project) {
//            $componentsArr = [];
//            foreach ($project->getComponents() as $component) {
//                $componentsArr[] = [
//                    'id' => $component->getId(),
//                    'name' => $component->getName(),
//                    'description' => $component->getDescription(),
//                    'category' => $component->getCategory()->value,
//                    'origin' => $component->getOrigin()->value,
//                    // Ajoute ici d'autres champs utiles pour ton frontend, ex: image, dates...
//                ];
//            }
//
//            $timelineData[] = [
//                'projectId' => $project->getId(),
//                'projectName' => $project->getTitle(),
//                'components' => $componentsArr,
//            ];
//        }
        $jsonProfile = $this->serializer->serialize($components, 'json',['groups'=>['timeline:read']]);

        return new JsonResponse($jsonProfile, JsonResponse::HTTP_OK,[], true);
    }
}
