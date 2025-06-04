<?php
// tests/ConnectionTest.php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();

        // 1) Boot the kernel in "test" mode and get a client
        $this->client = static::createClient();

        // 2) Fetch EntityManager via the "doctrine" service
        $this->entityManager = $this->client
            ->getContainer()
            ->get('doctrine')
            ->getManager();

        // 3) Fetch Symfony’s UserPasswordHasher
        $this->hasher = $this->client
            ->getContainer()
            ->get(UserPasswordHasherInterface::class);

        // 4) Truncate users table so each test starts fresh
        $conn = $this->entityManager->getConnection();
        try {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
            $conn->executeStatement('TRUNCATE TABLE users;');
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {
            // If TRUNCATE isn’t supported, fall back to DELETE
            $conn->executeStatement('DELETE FROM users;');
        }
    }
    public function testDatabaseNameIsCorrect(): void
    {


        // 2) Get Doctrine’s connection from the service container
        $connection = $this->entityManager->getConnection();


        // 3) Fetch the “current database name” directly via SQL
        $currentDb = $connection->executeQuery('SELECT DATABASE()')->fetchOne();


        // 4) Assert that it is "fkb_db_test"
        $this->assertEquals(
            'fkb_db_test',
            $currentDb,
            sprintf('Expected Doctrine to connect to fkb_db_test, but got "%s".', $currentDb)
        );
    }
    public function testGetReturnsNotLoggedIn(): void
    {

        // Make a GET request to /api/me without any authentication
        $this->client->request('GET', '/api/me');

        // Assert we get a 401 Unauthorized
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }

    public function testGetReturnsNotValidPassword(): void
    {
        // Create the HTTP client (this also boots the kernel under the hood)
        $this->createUser('j@example.com', 'correct-password');

        $this->client->request('POST', '/api/login',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode([
            'email' => 'j@example.com',
            'password' => 'correct-passwor',
        ]));

        $response = $this->client->getResponse();

        $this->assertEquals(401, $response->getStatusCode());

        // 4) Assert JSON body contains { "error": "Invalid credentials" }
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);

        // 1) Hash the plain password exactly as Symfony does
        $hashed = $this->hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        // 2) Give it a default role
        $user->setRole('user');

        // 3) Persist and flush to the test database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
