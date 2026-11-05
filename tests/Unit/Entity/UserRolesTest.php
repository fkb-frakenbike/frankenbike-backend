<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserRolesTest extends TestCase
{
    public function testUserHasRoleUserOnly(): void
    {
        $user = (new User())->setRole('user');
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testAdminHasRoleUserAndRoleAdmin(): void
    {
        $user = (new User())->setRole('admin');
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }
}
