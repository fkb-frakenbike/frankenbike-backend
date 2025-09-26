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
use App\Service\ProjectPhotoStorage;

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
            $data = json_decode($request->getContent(), true) ?? [];
            $this->logger->info(json_encode($data, JSON_PRETTY_PRINT));
            $this->logger->info($user->getEmail());


            // require title
            if (empty($data['title'])) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'title is required'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $project = new Project();
            $project->setUser($user);
            $project->setTitle($data['title']);

            // description can be null in your SQL schema
            $project->setDescription($data['description'] ?? null);

            // image_url is NOT NULL in your SQL -> use empty string until /cover upload
            $project->setImageUrl($data['imageUrl'] ?? '');


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
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10))); // Limite max à 100

        $offset = ($page - 1) * $limit;

        $projectRepo = $this->em->getRepository(Project::class);

        $projects = $projectRepo->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        $total = $projectRepo->count([]);


        $jsonProjects = $this->serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

        return new JsonResponse([
        'data' => json_decode($jsonProjects),
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'hasMore' => ($offset + $limit) < $total
        ],JsonResponse::HTTP_OK,[]
        );
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

    #[Route('/api/projects/{id}/cover', name: 'upload_project_cover', methods: ['POST'])]
    public function uploadProjectCover(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ProjectPhotoStorage $photoStorage
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error'=>'Authentication required'], Response::HTTP_UNAUTHORIZED);

        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) return new JsonResponse(['error'=>'Project not found'], Response::HTTP_NOT_FOUND);
        if ($project->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error'=>'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');
        if (!$file) return new JsonResponse(['error'=>'Missing form field "file"'], 400);

        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array((string)$file->getMimeType(), $allowed, true)) return new JsonResponse(['error'=>'Invalid file type'], 400);
        if (($file->getSize() ?? 0) > 10 * 1024 * 1024) return new JsonResponse(['error'=>'File too large'], 400);

        $mime = (string)($file->getMimeType() ?? '');
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => strtolower(pathinfo((string)$file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin'),
        };

        // project-covers/<projectId>/<projectId>.<ext>
        $key = sprintf('%d/%d.%s', $project->getId(), $project->getId(), $ext);

        $photoStorage->upload($file, $key);
        $url = $photoStorage->publicUrl($key) ?? '';

        $project->setImageUrl($url);
        $em->flush();

        $json = $serializer->serialize($project, 'json', ['groups' => ['project:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

}
