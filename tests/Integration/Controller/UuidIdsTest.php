<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class UuidIdsTest extends WebTestCase
{
    private const UUID_V7 = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->createQuery("DELETE FROM App\\Entity\\Profile p WHERE p.user IN (SELECT u.id FROM App\\Entity\\User u WHERE u.email LIKE 'uuid-%')")->execute();
        $this->em->createQuery("DELETE FROM App\\Entity\\User u WHERE u.email LIKE 'uuid-%'")->execute();
    }

    private function admin(): User
    {
        $user = new User();
        $user->setEmail('uuid-admin@example.com');
        $user->setRole('admin');
        $user->setPassword('not-used');
        $profile = new Profile();
        $profile->setFirstName('U');
        $profile->setUser($user);
        $user->setProfile($profile);
        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN_COOKIE', $jwt));

        return $user;
    }

    public function testSignupReturnsCanonicalUuidV7(): void
    {
        $this->client->jsonRequest('POST', '/api/users', ['email' => 'uuid-signup@example.com', 'password' => 'secret123']);
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsString($data['id']);
        $this->assertMatchesRegularExpression(self::UUID_V7, $data['id']);
    }

    public function testGetUserByUuidWorks(): void
    {
        $admin = $this->admin();
        $this->client->request('GET', '/api/users/'.$admin->getId());
        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame((string) $admin->getId(), $data['id']);
    }

    public function testUserWithNonUuidIdIs404(): void
    {
        $this->admin();
        $this->client->request('GET', '/api/users/123');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownValidUuidIs404(): void
    {
        $this->admin();
        $this->client->request('GET', '/api/users/01890a5d-ac96-774b-bcce-b302099a8057');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPublicUserProjectsRejectsNonUuid(): void
    {
        $this->client->request('GET', '/api/users/123/projects');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPublicProjectComponentsRejectsNonUuid(): void
    {
        $this->client->request('GET', '/api/projects/123/components');
        $this->assertResponseStatusCodeSame(404);
    }
}
