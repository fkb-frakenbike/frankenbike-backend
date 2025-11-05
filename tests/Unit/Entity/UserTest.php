<?php
namespace App\Tests\Unit\Entity;




use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCanSetAndGetBasicData(): void
    {
        $user = new User();

        $user->setEmail('test@example.com');
        $user->setPassword('hashedpassword');
        $user->setRole('admin'); // internal value is 'admin' or 'user'

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('hashedpassword', $user->getPassword());
        $this->assertSame('admin', $user->getRole());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testGetRolesReturnsRoleUserByDefault(): void
    {
        $user = new User();
        // no role explicitly set → default 'user'

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesMapsAdminCorrectly(): void
    {
        $user = new User();
        $user->setRole('admin');

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertCount(1, $roles);
    }
}
