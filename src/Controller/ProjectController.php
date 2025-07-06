<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;



class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer
    ) {}

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function createProject(Request $request): JsonResponse
    {
        echo "hello \n";
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        var_dump($user);
        var_dump($data);

    }

    #[Route('/api/projects', name: 'get_projects', methods: ['GET'])]
    public function getAllProjects(Request $request): JsonResponse
    {

        $projects = $this->em->getRepository(Project::class)->findAll();
        $jsonProjects = $this->serializer->serialize($projects, 'json');

        return new JsonResponse($jsonProjects,JsonResponse::HTTP_OK);
    }

}
