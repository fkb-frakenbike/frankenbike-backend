<?php

namespace App\Tests\Integration\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleHierarchyTest extends KernelTestCase
{
    public function testAdminReachesRoleUser(): void
    {
        self::bootKernel();
        $hierarchy = static::getContainer()->get(RoleHierarchyInterface::class);
        $this->assertContains('ROLE_USER', $hierarchy->getReachableRoleNames(['ROLE_ADMIN']));
    }
}
