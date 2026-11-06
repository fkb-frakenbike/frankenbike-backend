<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Login itself is covered by SessionAuthTest. */
class AuthControllerTest extends WebTestCase
{
    public function testApiMeRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
