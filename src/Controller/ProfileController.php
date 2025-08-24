<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Profile;

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
        $profile = $em->getRepository(Profile::class)->findOneBy(['user' => $userId]);
        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $jsonProfile = $serializer->serialize($profile, 'json',['groups'=>['profile:read']]);
        return new JsonResponse($jsonProfile, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/profiles/user/{userId}', name: 'update_profile_by_user', methods: ['PUT'])]
    public function updateProfileByUserId(int $userId, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
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
        $em->persist($profile);
        $em->flush();

        $jsonProfile = $serializer->serialize($profile, 'json',['groups'=>['profile:read']]);
        return new JsonResponse($jsonProfile, JsonResponse::HTTP_OK, [], true);
    }
}
