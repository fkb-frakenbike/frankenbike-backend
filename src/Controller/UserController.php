<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Profile;

class UserController extends AbstractController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher
    ): JsonResponse 
    {
        // Parse JSON request data
        $data = json_decode($request->getContent(), true);
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
        return new JsonResponse(['error' => 'Email already exists'], JsonResponse::HTTP_BAD_REQUEST);
}

        // Create new User entity and set its properties
        $user = new User();
        $user->setEmail($data['email']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }
        $profile = new Profile();
        $profile->setFirstName($data['firstname'] ?? '');
        $profile->setUser($user);
        $user->setProfile($profile);

        // Save the new user to the database
        $em->persist($user);
        $em->persist($profile);
        $em->flush();


        $jsonUser = $serializer->serialize($user, 'json', ['groups' => ['user:read']]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_CREATED,[],true);
    }

    #[Route('/api/users', name: 'list_users', methods: ['GET'])]
    public function getAllUsers(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $this->logger->error('HELLO From the backend YOLO!!');

        $users = $em->getRepository(User::class)->findAll();
        $jsonUsers = $serializer->serialize($users, 'json',['groups'=>['user:read']]);
        return new JsonResponse($jsonUsers, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserById(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $jsonUser = $serializer->serialize($user, 'json',['groups'=>['user:read']]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse 
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $em->remove($user);
        $em->flush();
        return new JsonResponse(['message' => 'User deleted successfully'], JsonResponse::HTTP_OK);
    }
}
