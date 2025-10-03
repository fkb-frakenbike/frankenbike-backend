<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Project
 */
class ProjectTest extends TestCase
{
    public function testCanGetAndSetData(): void
    {
        $project = new Project();

        $user = new User();
        $project->setUser($user);
        $project->setTitle('Mon projet');
        $project->setDescription('Description du projet');
        $project->setImageUrl('https://exemple.com/image.jpg');

        $this->assertSame($user, $project->getUser());
        $this->assertEquals('Mon projet', $project->getTitle());
        $this->assertEquals('Description du projet', $project->getDescription());
        $this->assertEquals('https://exemple.com/image.jpg', $project->getImageUrl());
        $this->assertInstanceOf(\DateTimeImmutable::class, $project->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $project->getUpdatedAt());
    }
}