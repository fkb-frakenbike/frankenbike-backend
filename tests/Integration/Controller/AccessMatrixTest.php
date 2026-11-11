<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Matrice d'accès E1 : users / profiles.
 * Authentification : session du pare-feu "api" (loginUser).
 */
class AccessMatrixTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        // what the front sends on every write (see CsrfGuardSubscriber)
        $this->client->setServerParameter('HTTP_X_REQUESTED_WITH', 'fkb');
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery("DELETE FROM App\\Entity\\Profile p WHERE p.user IN (SELECT u.id FROM App\\Entity\\User u WHERE u.email LIKE 'e1-%')")->execute();
        $this->em->createQuery("DELETE FROM App\\Entity\\User u WHERE u.email LIKE 'e1-%'")->execute();
    }

    private function makeUser(string $email, string $role = 'user'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRole($role);
        $user->setPassword('not-used');
        $profile = new Profile();
        $profile->setFirstName('E1');
        $profile->setUser($user);
        $user->setProfile($profile);
        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->client->loginUser($user, 'api');
    }

    public function testSignupIgnoresRoleFromBody(): void
    {
        $this->client->jsonRequest('POST', '/api/users', [
            'email' => 'e1-signup@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->em->clear();
        $created = $this->em->getRepository(User::class)->findOneBy(['email' => 'e1-signup@example.com']);
        $this->assertNotNull($created);
        $this->assertSame('user', $created->getRole());
    }

    public function testListUsersAnonymousIs401(): void
    {
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListUsersAsUserIs403(): void
    {
        $this->loginAs($this->makeUser('e1-user@example.com'));
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListUsersAsAdminIs200(): void
    {
        $this->loginAs($this->makeUser('e1-admin@example.com', 'admin'));
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetUserAnonymousIs401(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->client->request('GET', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserAsOtherUserIs403(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($this->makeUser('e1-other@example.com'));
        $this->client->request('GET', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetUserAsOwnerIs200(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($target);
        $this->client->request('GET', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetUserAsAdminIs200(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($this->makeUser('e1-admin@example.com', 'admin'));
        $this->client->request('GET', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDeleteUserAsOtherUserIs403(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($this->makeUser('e1-other@example.com'));
        $this->client->request('DELETE', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteUserAsOwnerIs200(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($target);
        $this->client->request('DELETE', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDeleteUserAsAdminIs200(): void
    {
        $target = $this->makeUser('e1-target@example.com');
        $this->loginAs($this->makeUser('e1-admin@example.com', 'admin'));
        $this->client->request('DELETE', '/api/users/'.$target->getId());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListProfilesAnonymousIs401(): void
    {
        $this->client->request('GET', '/api/profiles');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListProfilesAsUserIs403(): void
    {
        $this->loginAs($this->makeUser('e1-user@example.com'));
        $this->client->request('GET', '/api/profiles');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListProfilesAsAdminIs200(): void
    {
        $this->loginAs($this->makeUser('e1-admin@example.com', 'admin'));
        $this->client->request('GET', '/api/profiles');
        $this->assertResponseStatusCodeSame(200);
    }
}
