<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCanGetAndSetData(): void
    {
        $user = new User();

        $user->setEmail('test@test.com');
        $user->setPassword('password');
        $user->setRole('ROLE_USER');

        self::assertEquals('test@test.com', $user->getEmail());
        self::assertEquals('password', $user->getPassword());
        // self::assertSame(1, $this->mockHttpClient->getRequestsCount());

        self::assertEquals('ROLE_USER', $user->getRole());

        self::assertEquals('test@test.com', $user->getUserIdentifier());
        //        self::assertContains("ROLE_USER",$user->getRoles());
    }

    //    public function testAddAndRemoveProject(): void
    //    {
    //        $user = new User();
    //
    //        $initiallyEmpty = $user->getProjects();
    //        self::assertCount(0, $initiallyEmpty, 'A new User should start with zero projects.');
    //
    //        $project = new Project();
    //
    //        $user->addProject($project);
    //
    //        $afterAddCollection = $user->getProjects();
    //        self::assertCount(1, $afterAddCollection, 'After addProject(), there should be exactly one project in the collection.');
    //
    //        $firstProject = $afterAddCollection->first();
    //        self::assertSame($project, $firstProject, 'The project we added must be the same instance stored in the collection.');
    //
    //        self::assertSame($user, $project->getUser(), 'After addProject(), $project->getUser() should return the User.');
    //
    //        $user->removeProject($project);
    //
    //        self::assertCount(0, $user->getProjects(), 'After removeProject(), the User’s project collection should be empty again.');
    //
    //
    //        self::assertNull(
    //            $project->getUser(),
    //            'After removeProject(), $project->getUser() should be null (owning side cleared).'
    //        );
    //    }
}
