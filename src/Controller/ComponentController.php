<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Component;
use App\Entity\Project;
use App\Enum\ComponentCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class ComponentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
    ){}

    #[Route('/api/projects/{projectId}/components', methods: ['POST'])]
    public function create(Request $req, int $projectId) {
        try {
            // 1. getUser, ensure authenticated
            $user = $this->getUser();
            if(!$user) {
                $this->logger->info("User not logged in");
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // 2. fetch project by id, check ownership
            $project = $this->em->getRepository(Project::class)->find($projectId);
            if(!$project) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }
            if ($project->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Not authorized'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // 3. decode body: name, description, category, origin
            $data = json_decode($req->getContent(), true);

            $categoryString = $data['category'];
            $originString = $data['origin'];
            // 4. validate: category/origin in allowed values
            if (!ComponentCategory::tryFrom(($categoryString))) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid category'
                ], 400);
            }
            if (!ComponentCategory::tryFrom(($originString))) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid category'
                ], 400);
            }
            // 5. create new Component, set fields, link to project
            $component = new Component();
            $component->setName($data['name']);
            $component->setDescription($data['description']);
            $component->setCategory($data['category']);
            $component->setOrigin($data['origin']);

            $component->setProject($project);

            // 6. persist and flush
            $this->em->persist($component);
            $this->em->flush();
            // 7. return 201 + data

        } catch(\Throwable $exception){

        }
    }

    #[Route('/api/projects/{projectId}/components', methods: ['GET'])]
    public function list(int $projectId) {
        // 1. fetch project, check ownership
        // 2. fetch components by project_id
        // 3. return as JSON
    }

    #[Route('/api/components/{id}', methods: ['GET'])]
    public function show(int $id) {
        // fetch, check ownership, return
    }

    #[Route('/api/components/{id}', methods: ['PUT', 'PATCH'])]
    public function update(Request $req, int $id) {
        // fetch, check ownership
        // decode, update allowed fields, flush, return
    }

    #[Route('/api/components/{id}', methods: ['DELETE'])]
    public function delete(int $id) {
        // fetch, check ownership, remove, flush, return
    }
}
