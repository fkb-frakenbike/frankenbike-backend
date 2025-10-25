<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SubUserController extends AbstractController
{
    public function __construct(private SerializerInterface $serializer) {}

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function apiMe(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Gestion automatique des références circulaires
        $context = [
            'groups' => ['user:read', 'project:read'],
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // lorsqu’un objet se répète, ne renvoie que son ID
            },
        ];

        $json = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }
}
