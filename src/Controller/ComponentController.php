<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Component;
use App\Entity\Project;
use App\Enum\ComponentCategory;
use App\Enum\ComponentOrigin;
use App\Service\ComponentPhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
    public function create(Request $req, int $projectId, ComponentPhotoStorage $photoStorage): JsonResponse
    {
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

            // 3. read multipart fields (NOT JSON)
            $name        = (string) $req->request->get('name', '');
            $description = (string) ($req->request->get('description') ?? '');
            $categoryRaw = (string) $req->request->get('category', '');
            $originRaw   = (string) $req->request->get('origin', '');

            if ($name === '') {
                return new JsonResponse(['status' => 'error', 'message' => 'Name is required'], 400);
            }

            $category = ComponentCategory::tryFrom($categoryRaw);
            if (!$category) {
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid category'], 400);
            }

            $origin = ComponentOrigin::tryFrom($originRaw);
            if (!$origin) {
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid origin'], 400);
            }

            // file is required
            $file = $req->files->get('file');
            if (!$file) {
                return new JsonResponse(['status' => 'error', 'message' => 'Missing form field "file"'], 400);
            }
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (!in_array((string)$file->getMimeType(), $allowed, true)) {
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid file type'], 400);
            }
            if (($file->getSize() ?? 0) > 10 * 1024 * 1024) {
                return new JsonResponse(['status' => 'error', 'message' => 'File too large'], 400);
            }

            // 4. create entity first (to get its DB id), then upload, then save photo meta
            $component = new Component();
            $component->setProject($project);
            $component->setName($name);
            $component->setDescription($description);
            $component->setCategory($category);
            $component->setOrigin($origin);

            $this->em->persist($component);
            $this->em->flush(); // now we have $component->getId()

            $ext = strtolower($file->guessExtension() ?: 'bin');
            // key shape in R2: components/<projectId>/<componentId>.<ext>
            $key = sprintf('%d/%d.%s', $projectId, $component->getId(), $ext);

            // upload to R2 (Flysystem)
            $photoStorage->upload($file, $key);

            // save photo meta on the component
            $component->setPhotoS3Key($key);
            $component->setPhotoMimeType($file->getMimeType() ?? 'image/jpeg');
            $component->setPhotoSize((int) ($file->getSize() ?? 0));

            $this->em->flush();


            $jsonComponent = $this->serializer->serialize($component, 'json',['groups' => ['component:read']]);
            // 7. return 201 + data
            return new JsonResponse($jsonComponent, Response::HTTP_CREATED,[],true);
        } catch(\Throwable $exception){
            // Log the error and return a clear message
            $this->logger->error('Error in createComponent: '.$exception->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Something went wrong. '.$exception->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/projects/{projectId}/components', methods: ['GET'])]
    public function getByProjectId(int $projectId)
    {
        // 1. fetch project, check ownership
        $project = $this->em->getRepository(Project::class)->find($projectId);
        $user = $this->getUser();
        if(!$user) {
            $this->logger->info("User not logged in");
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!$project) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Project not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        if($project->getUser()->getId()!== $user->getId()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Authentication required'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // 2. fetch components by project_id
        $components = $project->getComponents();

        // 3. return as JSON
        $jsonComponent = $this->serializer->serialize($components, 'json',['groups' => ['component:read']]);
        return new JsonResponse($jsonComponent, Response::HTTP_OK,[],true);
    }

    #[Route('/api/components/{id}', methods: ['GET'])]
    public function getComponent(int $id)
    {
        try {
            // fetch, check ownership, return
            $user= $this->getUser();
            if(!$user) {
                $this->logger->info("User not logged in");
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }
            $component = $this->em->getRepository(Component::class)->find($id);
            if(!$component){
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Component not found'
                ], Response::HTTP_NOT_FOUND);
            }
            if ($component->getProject()->getUser()->getId() !== $user->getId()) {
                return new JsonResponse(['status' => 'error',
                    'message' => 'Authentication required'
                ], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(
                $component,
                Response::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Error in createComponent: '.$exception->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Something went wrong. '.$exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    #[Route('/api/components/{id}', methods: ['PUT', 'PATCH'])]
    public function updateComponent(Request $req, int $id)
    {
        $user = $this->getUser();
        if(!$user){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User not logged in'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // fetch, check ownership
        $component = $this->em->getRepository(Component::class)->find($id);
        if(!$component){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Component not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // decode, update allowed fields, flush, return
        if ($component->getProject()->getUser()->getId() !== $user->getId()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Authentication required'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($req->getContent(), true);

        //Update allowed fields
        if(isset($data['name'])) $component->setName($data['name']);
        if(isset($data['description'])) $component->setDescription($data['description']);
        if(isset($data['category'])) $component->setCategory($data['category']);
        if(isset($data['origin'])) $component->setOrigin($data['origin']);


//        no need to persist this data since it is already created this component
        //$this->em->persist($component);
        $this->em->flush();

        $jsonComponent = $this->serializer->serialize($component, 'json',['groups' => ['component:read']]);
        return new JsonResponse($jsonComponent, Response::HTTP_OK,[],true);

    }

    #[Route('/api/components/{id}', methods: ['DELETE'])]
    public function deleteComponent(int $id)
    {
        try {
            // fetch, check ownership, remove, flush, return
            $user = $this->getUser();
            if(!$user){
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'User not logged in'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }
            $component = $this->em->getRepository(Component::class)->find($id);
            if(!$component){
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Component not found'
                ], Response::HTTP_NOT_FOUND);
            }
            if ($component->getProject()->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }
            $this->em->remove($component);
            $this->em->flush();
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Something went wrong: ' . $exception->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
