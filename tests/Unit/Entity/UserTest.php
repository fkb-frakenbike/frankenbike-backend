<?php

namespace App\Tests\Unit\Entity;


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

        self::assertEquals("test@test.com", $user->getEmail());
        self::assertEquals("password", $user->getPassword());

        self::assertEquals("ROLE_USER", $user->getRole());

        self::assertEquals("test@test.com",$user->getUserIdentifier());
    }
}