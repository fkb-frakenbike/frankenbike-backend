<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** S2: real logout (session + remember-me series), cookie theft answered in 401, immediate credential changes. */
class LogoutTest extends WebTestCase
{
    private const EMAIL = 's2-user@example.com';
    private const PASSWORD = 'correct horse battery';
    // the test env uses mock file sessions, whose cookie keeps the default PHP-mock name
    private const SESSION_COOKIE = 'MOCKSESSID';
    private const HEADERS = ['HTTP_ORIGIN' => 'http://localhost:3000', 'HTTP_X_REQUESTED_WITH' => 'fkb'];

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery("DELETE FROM App\\Entity\\Profile p WHERE p.user IN (SELECT u.id FROM App\\Entity\\User u WHERE u.email LIKE 's2-%')")->execute();
        $this->em->createQuery("DELETE FROM App\\Entity\\User u WHERE u.email LIKE 's2-%'")->execute();
        $this->em->getConnection()->executeStatement('DELETE FROM rememberme_token WHERE username LIKE ?', ['s2-%']);

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setRole('user');
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $profile = new Profile();
        $profile->setFirstName('S2');
        $profile->setUser($user);
        $user->setProfile($profile);
        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();
    }

    private function login(bool $remember): void
    {
        $this->client->jsonRequest('POST', '/api/login', ['email' => self::EMAIL, 'password' => self::PASSWORD, 'rememberMe' => $remember], self::HEADERS);
        $this->assertResponseStatusCodeSame(200);
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

    private function remembermeValue(): string
    {
        return $this->cookie('REMEMBERME')->getValue();
    }

    private function seriesCount(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM rememberme_token WHERE username = ?', [self::EMAIL]);
    }

    private function onlyRememberMe(string $value): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set(new Cookie('REMEMBERME', $value, null, '/', 'localhost', false, true, false));
    }

    /** Real series, wrong token: cookie layout is class:user:expires:series:token. */
    private function forgedCookie(): string
    {
        [$class, $user, $expires, $value] = explode(':', $this->remembermeValue(), 4);
        [$series] = explode(':', $value);

        return implode(':', [$class, $user, $expires, $series, 'forged-token-value']);
    }

    private function logout(): void
    {
        $this->client->request('POST', '/api/logout', [], [], self::HEADERS);
    }

    public function testLogoutKeepsStatus200AndJsonShape(): void
    {
        $this->login(false);
        $this->logout();

        $this->assertResponseStatusCodeSame(200);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['message']);
    }

    public function testMeIs401AfterLogout(): void
    {
        $this->login(false);
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(200);

        $this->logout();

        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLogoutClearsSessionAndRememberMeCookies(): void
    {
        $this->login(true);
        $this->logout();

        foreach ([self::SESSION_COOKIE, 'REMEMBERME'] as $name) {
            $cookie = $this->cookie($name);
            $this->assertNotNull($cookie, "$name must be cleared by the logout response");
            $this->assertTrue($cookie->isCleared(), "$name must be expired");
        }
    }

    public function testLogoutDeletesTheRememberMeSeriesOnTheServer(): void
    {
        $this->login(true);
        $value = $this->remembermeValue();
        $this->assertSame(1, $this->seriesCount());

        $this->logout();
        $this->assertSame(0, $this->seriesCount());

        // a copy of the cookie kept by someone else is dead too
        $this->onlyRememberMe($value);
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLogoutWithoutAnyCookieIsStill200(): void
    {
        $this->logout();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testStolenOrReplayedRememberMeCookieIsA401NotA500(): void
    {
        $this->login(true);
        // same series, another token value: what an attacker replaying an outdated cookie presents
        $this->onlyRememberMe($this->forgedCookie());

        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertIsString(json_decode($this->client->getResponse()->getContent(), true)['error']);
    }

    public function testMalformedRememberMeCookieIsA401NotA500(): void
    {
        $this->onlyRememberMe('class:user:not-a-timestamp:series:token');

        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCookieTheftKillsTheWholeSeries(): void
    {
        $this->login(true);
        $this->onlyRememberMe($this->forgedCookie());
        $this->client->request('GET', '/api/me');

        $this->assertSame(0, $this->seriesCount(), 'the series of a stolen cookie must not survive');
    }

    public function testPasswordChangeEndsTheSessionAtOnce(): void
    {
        $this->login(false);
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(200);

        $hash = static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword(new User(), 'another password');
        $this->em->getConnection()->executeStatement('UPDATE users SET password = ? WHERE email = ?', [$hash, self::EMAIL]);

        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }
}
