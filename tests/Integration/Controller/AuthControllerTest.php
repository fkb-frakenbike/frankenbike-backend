<?php

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = $this->client->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);

        // Cleanup old test users
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'test-login@example.com']);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function testLoginSuccessSetsCookie(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('test-login@example.com');
        $user->setRole('user');
        $user->setPassword($this->hasher->hashPassword($user, '123456'));
        $this->em->persist($user);
        $this->em->flush();

        // Login
        $this->client->jsonRequest('POST', '/api/login', [
            'email' => 'test-login@example.com',
            'password' => '123456',
            'rememberMe' => true,
        ]);

        $this->assertResponseIsSuccessful();

        $cookies = $this->client->getResponse()->headers->getCookies();
        $found = false;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'AUTH_TOKEN_COOKIE') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'AUTH_TOKEN_COOKIE should exist after successful login');
    }

    public function testApiMeRequiresAuth(): void
    {
        $this->client->request('GET', '/api/me');
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertTrue(
            $status === 401 || $status === 403,
            sprintf('Expected 401 or 403, got %d', $status)
        );
    }
}
