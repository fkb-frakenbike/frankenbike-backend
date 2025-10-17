<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Profile;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ProfilePhotoStorage;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends AbstractController
{
    #[Route('/api/profiles', name: 'list_profiles', methods: ['GET'])]
    public function getAllProfiles(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $profiles = $em->getRepository(Profile::class)->findAll();
        $jsonProfiles = $serializer->serialize($profiles, 'json',['groups'=>['profile:read']]);
        return new JsonResponse($jsonProfiles, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/profiles/user/{userId}', name: 'get_profile_by_user', methods: ['GET'])]
    public function getProfileByUserId(int $userId, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if ($user->getId() !== $userId /* && !$this->isGranted('ROLE_ADMIN') */) {
            return new JsonResponse(['error' => 'Not authorized'], JsonResponse::HTTP_FORBIDDEN);
        }

        $profile = $em->getRepository(Profile::class)->findOneBy(['user' => $userId]);
        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $jsonProfile = $serializer->serialize($profile, 'json',['groups'=>['profile:read']]);
        return new JsonResponse($jsonProfile, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/profiles/user/{userId}', name: 'update_profile_by_user', methods: ['PUT','PATCH'])]
    public function updateProfileByUserId(int $userId, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if ($user->getId() !== $userId /* && !$this->isGranted('ROLE_ADMIN') */) {
            return new JsonResponse(['error' => 'Not authorized'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $profile = $em->getRepository(Profile::class)->findOneBy(['user' => $userId]);
        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Update profile fields
        if (isset($data['first_name'])) {
            $profile->setFirstName($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $profile->setLastName($data['last_name']);
        }
        if (isset($data['birthdate'])) {
            $profile->setBirthdate(new \DateTime($data['birthdate']));
        }
        if (isset($data['photo_url'])) {
            $profile->setPhotoUrl($data['photo_url']);
        }

        // Save changes to the database
        $em->flush();

        $jsonProfile = $serializer->serialize($profile, 'json',['groups'=>['profile:read']]);
        return new JsonResponse($jsonProfile, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/profiles/user/{userId}/photo', name: 'upload_profile_photo', methods: ['POST'])]
    public function uploadProfilePhoto(
        int $userId,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ProfilePhotoStorage $photoStorage
    ): JsonResponse {
        // require auth
        $user = $this->getUser();
        if (!$user || $user->getId() !== $userId) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // load profile
        $profile = $em->getRepository(Profile::class)->findOneBy(['user' => $userId]);
        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], Response::HTTP_NOT_FOUND);
        }

        // get file from multipart
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Missing form field "file"'], 400);
        }

        // validate mime/size
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array((string)$file->getMimeType(), $allowed, true)) {
            return new JsonResponse(['error' => 'Invalid file type'], 400);
        }
        if (($file->getSize() ?? 0) > 10 * 1024 * 1024) {
            return new JsonResponse(['error' => 'File too large'], 400);
        }

        // pick extension without symfony/mime
        $mime = (string)($file->getMimeType() ?? '');
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => strtolower(pathinfo((string)$file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin'),
        };

        // key shape: profiles/<userId>/<componentId or ulid>.<ext>
        // for profile we’ll do: profiles/<userId>/<userId>.<ext> (one photo per user)
        $key = sprintf('%d/%d.%s', $userId, $userId, $ext);

        // upload to R2
        $photoStorage->upload($file, $key);

        // compute public URL & save on profile
        $url = $photoStorage->publicUrl($key) ?? '';
        $profile->setPhotoUrl($url);
        $em->flush();

        $json = $serializer->serialize($profile, 'json', ['groups'=>['profile:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
