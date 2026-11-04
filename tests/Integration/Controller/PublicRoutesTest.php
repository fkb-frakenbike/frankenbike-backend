<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicRoutesTest extends WebTestCase
{
    public function testUserProjectsIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/999999/projects');
        $this->assertNotSame(401, $client->getResponse()->getStatusCode());
    }

    public function testProjectComponentsIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/projects/999999/components');
        $this->assertNotSame(401, $client->getResponse()->getStatusCode());
    }

    public function testOtherApiRouteStillRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
