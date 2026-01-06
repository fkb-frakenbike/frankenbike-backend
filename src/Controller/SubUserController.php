<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Service\ComponentPhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SubUserController extends UserController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private ComponentPhotoStorage $componentPhotoStorage
    ) {}

    #[Route('/api/me', name: 'me', methods: ['GET'])]
    public function apiMe(SerializerInterface $serializer): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $userData = $serializer->normalize($currentUser, null, ['groups' => ['user:read']]);

        $projects = $this->em->getRepository(Project::class)->findBy(
            ['user' => $currentUser],
            ['createdAt' => 'DESC']
        );

        $this->logger->info('User projects retrieved', ['projects' => $projects]);

        $projectsJson = $serializer->serialize($projects, 'json', ['groups' => ['project:read']]);

        return new JsonResponse([
            'user' => $userData,
            'projects' => json_decode($projectsJson, true),
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/api/me', name: 'me_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];

        // Profile (email NON modifiable ici)
        $profile = $currentUser->getProfile();
        if (!$profile) {
            return new JsonResponse(['error' => 'Profil introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // ✅ firstName
        if (isset($data['firstName'])) {
            $profile->setFirstName($data['firstName']);
        }

        // ✅ birthdate
        if (isset($data['birthdate'])) {
            $birthDateStr = $data['birthdate'];
            if ($birthDateStr) {
                $birthDate = \DateTime::createFromFormat('Y-m-d', $birthDateStr);
                if ($birthDate !== false) {
                    $profile->setBirthdate($birthDate);
                }
            } else {
                $profile->setBirthdate(null);
            }
        }

        $this->em->flush();

        $updatedUserData = $serializer->normalize($currentUser, null, ['groups' => ['user:read']]);
        return new JsonResponse(['user' => $updatedUserData]);
    }


    #[Route('/api/me/photo', name: 'me_photo', methods: ['POST'])]
    public function uploadPhoto(Request $request, ComponentPhotoStorage $photoStorage): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $photoFile = $request->files->get('photo');
        if (!$photoFile instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Aucune photo envoyée'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $profile = $currentUser->getProfile();
            if (!$profile) {
                return new JsonResponse(['error' => 'Profil introuvable'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // ✅ EXACTEMENT comme ComponentController
            $ext = strtolower($photoFile->guessExtension() ?: 'jpg');
            $key = sprintf('profiles/%d.%s', $profile->getId(), $ext);

            // Upload S3/R2 (comme tes composants)
            $photoStorage->upload($photoFile, $key);

            // Sauvegarde URL + métadonnées (comme Component)
            $profile->setPhotoUrl($photoStorage->publicUrl($key));
            // Si Profile a ces champs :
            // $profile->setPhotoMimeType($photoFile->getMimeType() ?? 'image/jpeg');
            // $profile->setPhotoSize((int) $photoFile->getSize());

            $this->em->flush();

            return new JsonResponse([
                'message' => 'Photo mise à jour',
                'photoUrl' => $profile->getPhotoUrl()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Upload photo profil failed', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

}
