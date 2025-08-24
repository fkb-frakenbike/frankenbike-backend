<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface    $serializer,
        private LoggerInterface        $logger,
    )
    {
    }

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function createProject(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], Response::HTTP_UNAUTHORIZED);
            }
            $data = json_decode($request->getContent(), true);
            $this->logger->info(json_encode($data, JSON_PRETTY_PRINT));
            $this->logger->info($user->getEmail());
            $project = new Project();
            $project->setTitle($data['title']);
            $project->setUser($user);
            if ($data['description'] !== null) {
                $project->setDescription($data['description']);
            }
            $project->setImageUrl($data['imageUrl']);

            // Save changes to the database
            $this->em->persist($project);
            $this->em->flush();

            $projectJson = $this->serializer->serialize($project, 'json', ['groups' => 'project:read']);
            return new JsonResponse($projectJson, Response::HTTP_OK, [], true);

        } catch (\Throwable $exception) {
            // Log the error and return a clear message
            $this->logger->error('Error in createProject: ' . $exception->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Something went wrong. ' . $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/projects', name: 'get_projects', methods: ['GET'])]
    public function getAllProjects(Request $request): JsonResponse
    {
        $projects = $this->em->getRepository(Project::class)->findAll();

        //to avoid looping infintely because of going over thserializer that
        // always reaches the next child relationship and then the child will be
        // the parent and vice versa
//        $projectData = [];
//        foreach ($projects as $project) {
//            $projectData[] = [
//                'id' => $project->getId(),
//                'title' => $project->getTitle(),
//                'description' => $project->getDescription(),
//                'imageUrl' => $project->getImageUrl(),
//                'user' => [
//                    'id' => $project->getUser()?->getId(),
//                    'email' => $project->getUser()?->getEmail(),
//                ],
//                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
//                // etc.
//            ];
//        }

        $jsonProjects = $this->serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

        return new JsonResponse($jsonProjects, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/projects/{id}', name: 'get_project', methods: ['GET'])]
    public function getProject($id): JsonResponse
    {
        // Valide que $id est un entier (ou convertible)
        if (!is_numeric($id)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid project ID',
            ], Response::HTTP_BAD_REQUEST);
        }

        $id = (int) $id; // cast explicite en int

        $project = $this->em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Project not found',
            ], Response::HTTP_NOT_FOUND);
        }
//        $projectData = [
//            'id' => $project->getId(),
//            'title' => $project->getTitle(),
//            'description' => $project->getDescription(),
//            'imageUrl' => $project->getImageUrl(),
//            'user' => [
//                'id' => $project->getUser()?->getId(),
//                'email' => $project->getUser()?->getEmail(),
//            ],
//            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
//            // etc.
//        ];
        $jsonProject = $this->serializer->serialize($project, 'json', ['groups' => ['project:read']]);

        return new JsonResponse($jsonProject, Response::HTTP_OK, [], true);
    }

    #[Route('/api/projects/{id}', name: 'update_project', methods: ['PUT', 'PATCH'])]
    public function updateProject(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $project = $this->em->getRepository(Project::class)->find($id);

            if (!$project) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }


            // Make sure the user can only update their own projects (unless admin)
            if ($project->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Not authorized'
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);

            // Map input keys to setter methods
            $fields = [
                'title' => 'setTitle',
                'description' => 'setDescription',
                'imageUrl' => 'setImageUrl',
            ];

            foreach ($fields as $jsonField => $setter) {
                if (array_key_exists($jsonField, $data) && $data[$jsonField] !== null) {
                    if (method_exists($project, $setter)) {
                        $project->$setter($data[$jsonField]);
                    }
                }
            }
            $project->setUpdatedAt(new \DateTime());

            $this->em->flush();
            $jsonProject = $this->serializer->serialize($project, 'json', ['groups' => ['project:read']]);
            // Return updated project in response
            return new JsonResponse($jsonProject, Response::HTTP_OK, [], true);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/projects/{id}', name: 'delete_project', methods: ['DELETE'])]
    public function deleteProject(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $project = $this->em->getRepository(Project::class)->find($id);

            if (!$project) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // Make sure the user can only update their own projects (unless admin)
            if ($project->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Not authorized'
                ], JsonResponse::HTTP_FORBIDDEN);
            }
            $this->em->remove($project);
            $this->em->flush();


            return new JsonResponse([
                'status' => 'success',
                'message' => 'Project deleted successfully',
            ], JsonResponse::HTTP_OK);


        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
