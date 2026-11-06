<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Comment;
use App\Entity\Component;
use App\Entity\Like;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class EntityUuidTest extends TestCase
{
    public static function entities(): iterable
    {
        foreach ([User::class, Profile::class, Project::class, Component::class, Comment::class, Like::class] as $class) {
            yield $class => [$class];
        }
    }

    #[DataProvider('entities')]
    public function testNewEntityHasUuidV7Id(string $class): void
    {
        $entity = new $class();
        $this->assertInstanceOf(UuidV7::class, $entity->getId());
    }

    #[DataProvider('entities')]
    public function testIdsAreUnique(string $class): void
    {
        $this->assertFalse((new $class())->getId()->equals((new $class())->getId()));
    }
}
