<?php

namespace App\Tests\Integration\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PromoteUserCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->createQuery("DELETE FROM App\\Entity\\User u WHERE u.email LIKE 'e3-%'")->execute();
        $this->tester = new CommandTester((new Application(self::$kernel))->find('app:user:promote'));
    }

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->setEmail('e3-promote@example.com');
        $user->setRole($role);
        $user->setPassword('not-used');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function roleInDb(): string
    {
        $this->em->clear();

        return $this->em->getRepository(User::class)->findOneBy(['email' => 'e3-promote@example.com'])->getRole();
    }

    public function testPromotesUserToAdmin(): void
    {
        $this->makeUser('user');
        $this->assertSame(0, $this->tester->execute(['email' => 'e3-promote@example.com']));
        $this->assertSame('admin', $this->roleInDb());
    }

    public function testDemotesAdminToUser(): void
    {
        $this->makeUser('admin');
        $this->assertSame(0, $this->tester->execute(['email' => 'e3-promote@example.com', '--demote' => true]));
        $this->assertSame('user', $this->roleInDb());
    }

    public function testUnknownEmailFails(): void
    {
        $this->assertNotSame(0, $this->tester->execute(['email' => 'e3-unknown@example.com']));
    }
}
