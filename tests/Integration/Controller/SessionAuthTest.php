<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * S1: session firewall + json_login + persistent remember_me + anti-CSRF guard.
 * The headers below are what the front sends (fetch/axios with credentials).
 */
class SessionAuthTest extends WebTestCase
{
    private const EMAIL = 's1-user@example.com';
    private const PASSWORD = 'correct horse battery';
    // the test env uses mock file sessions, whose cookie keeps the default PHP-mock name
    private const SESSION_COOKIE = 'MOCKSESSID';
    private const ORIGIN = 'http://localhost:3000';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery("DELETE FROM App\\Entity\\Profile p WHERE p.user IN (SELECT u.id FROM App\\Entity\\User u WHERE u.email LIKE 's1-%')")->execute();
        $this->em->createQuery("DELETE FROM App\\Entity\\User u WHERE u.email LIKE 's1-%'")->execute();

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setRole('user');
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $profile = new Profile();
        $profile->setFirstName('S1');
        $profile->setUser($user);
        $user->setProfile($profile);
        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();
    }

    /** @param array<string,mixed> $body */
    private function login(array $body, array $headers = []): void
    {
        $this->client->jsonRequest('POST', '/api/login', $body, $headers + [
            'HTTP_ORIGIN' => self::ORIGIN,
            'HTTP_X_REQUESTED_WITH' => 'fkb',
        ]);
    }

    private function cookie(string $name): ?\Symfony\Component\HttpFoundation\Cookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $c) {
            if ($c->getName() === $name) {
                return $c;
            }
        }

        return null;
    }

    private function loginRemembered(): string
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD, 'rememberMe' => true]);
        $this->assertResponseStatusCodeSame(200);
        $remember = $this->cookie('REMEMBERME');
        $this->assertNotNull($remember, 'rememberMe=true must set the REMEMBERME cookie');

        return $remember->getValue();
    }

    /** Browser restart / expired session: the jar only keeps the given REMEMBERME value. */
    private function onlyRememberMe(string $value): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set(new Cookie('REMEMBERME', $value, null, '/', 'localhost', false, true, false));
    }

    // --- test n°2 (the risk): REMEMBERME re-authenticates a plain fetch GET ---
    public function testRememberMeReauthenticatesSimpleGetAfterSessionIsGone(): void
    {
        $value = $this->loginRemembered();
        $this->onlyRememberMe($value);

        // no custom header, no JSON content-type: exactly what a plain fetch('/api/me', {credentials:'include'}) sends
        $this->client->request('GET', '/api/me', [], [], ['HTTP_ORIGIN' => self::ORIGIN]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::EMAIL, json_decode($this->client->getResponse()->getContent(), true)['user']['email']);
    }

    /** Symfony re-issues the cookie (sliding 30 days) only when the token is older than 60 s. */
    private function ageToken(): void
    {
        $this->em->getConnection()->executeStatement("UPDATE rememberme_token SET lastUsed = lastUsed - interval '2 minutes' WHERE username = ?", [self::EMAIL]);
    }

    public function testRememberMeCookieIsReissuedOnceItIsOlderThanAMinute(): void
    {
        $value = $this->loginRemembered();
        $this->ageToken();
        $this->onlyRememberMe($value);

        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(200);
        $new = $this->cookie('REMEMBERME');
        $this->assertNotNull($new);
        $this->assertNotSame($value, $new->getValue());
        $this->assertGreaterThan(time() + 29 * 86400, $new->getExpiresTime(), 'sliding 30 days');
    }

    public function testOldRememberMeStillWorksRightAfterRotationAndIsNotCookieTheft(): void
    {
        $old = $this->loginRemembered();
        $this->ageToken();
        $this->onlyRememberMe($old);
        $this->client->request('GET', '/api/me'); // rotates
        $this->assertResponseStatusCodeSame(200);

        // a parallel request that left with the previous cookie, session cookie gone
        $this->onlyRememberMe($old);
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testNoRememberMeCookieWhenNotRequested(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD, 'rememberMe' => false]);

        $this->assertResponseStatusCodeSame(200);
        $cookie = $this->cookie('REMEMBERME');
        $this->assertTrue(null === $cookie || $cookie->isCleared(), 'no remember-me cookie is issued');
    }

    public function testLoginSetsSessionCookieAndKeepsResponseShape(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['message' => 'Connected'], json_decode($this->client->getResponse()->getContent(), true));
        $session = $this->cookie(self::SESSION_COOKIE);
        $this->assertNotNull($session);
        $this->assertTrue($session->isHttpOnly());
        $this->assertSame(0, $session->getExpiresTime(), 'browser session cookie');
        $this->assertSame('lax', $session->getSameSite());
        $this->assertNull($this->cookie('AUTH_TOKEN_COOKIE'));
    }

    public function testSessionIdIsRenewedOnLogin(): void
    {
        // session fixation: the attacker plants a known id before the victim logs in
        $this->client->getCookieJar()->set(new Cookie(self::SESSION_COOKIE, 'planted0123456789planted0123456789', null, '/', 'localhost', false, true, false));
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $session = $this->cookie(self::SESSION_COOKIE);
        $this->assertNotNull($session, 'a new session id is issued at login');
        $this->assertNotSame('planted0123456789planted0123456789', $session->getValue());
    }

    public function testWrongPasswordAndUnknownUserGiveTheSameErrorShape(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => 'nope']);
        $this->assertResponseStatusCodeSame(401);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['error']);

        $this->login(['email' => 's1-nobody@example.com', 'password' => 'nope']);
        $this->assertResponseStatusCodeSame(401);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['error']);
    }

    public function testAnonymousMeIs401WithErrorString(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['error']);
    }

    public function testRoleChangeEndsTheSessionAtOnce(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(200);

        $this->em->createQuery("UPDATE App\\Entity\\User u SET u.role = 'admin' WHERE u.email = :e")->setParameter('e', self::EMAIL)->execute();

        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);

        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->client->request('GET', '/api/profiles');
        $this->assertResponseStatusCodeSame(200);
    }

    // --- CSRF decision: SameSite=Lax + Origin allow-list + custom header on unsafe methods ---
    public function testUnsafeRequestWithoutCustomHeaderIsRejected(): void
    {
        $this->client->jsonRequest('POST', '/api/login', ['email' => self::EMAIL, 'password' => self::PASSWORD], ['HTTP_ORIGIN' => self::ORIGIN]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['error']);
    }

    public function testUnsafeRequestFromForeignOriginIsRejected(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD], ['HTTP_ORIGIN' => 'https://evil.example']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnsafeMultipartRequestWithHeaderIsAccepted(): void
    {
        $this->login(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        // multipart, no JSON body: passes the guard (then fails on validation, not 403)
        $this->client->request('POST', '/api/projects', [], [], ['HTTP_ORIGIN' => self::ORIGIN, 'HTTP_X_REQUESTED_WITH' => 'fkb']);

        $this->assertNotSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testSafeRequestNeedsNoCustomHeader(): void
    {
        $this->client->request('GET', '/api/ping', [], [], ['HTTP_ORIGIN' => 'https://evil.example']);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCorsPreflightAllowsTheCustomHeaderWithCredentials(): void
    {
        $this->client->request('OPTIONS', '/api/login', [], [], [
            'HTTP_ORIGIN' => self::ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,x-requested-with',
        ]);

        $h = $this->client->getResponse()->headers;
        $this->assertSame(self::ORIGIN, $h->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $h->get('Access-Control-Allow-Credentials'));
        $this->assertStringContainsStringIgnoringCase('x-requested-with', (string) $h->get('Access-Control-Allow-Headers'));
    }
}
