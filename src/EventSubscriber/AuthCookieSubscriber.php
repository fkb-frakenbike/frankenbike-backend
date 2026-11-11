<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/** Cookie edges of the api firewall: the logout answer and the remember-me cookie sanity check. */
class AuthCookieSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $sessionCookieName,
        private bool $secure,
        private string $sameSite,
        private string $domain,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // before the firewall (8), after the router (32)
            KernelEvents::REQUEST => ['dropMalformedRememberMe', 16],
            // above DefaultLogoutListener (64), which would otherwise answer with a redirect
            LogoutEvent::class => ['onLogout', 100],
        ];
    }

    /**
     * The firewall already invalidated the session and, through the remember-me handler, deleted the
     * series in rememberme_token and queued the REMEMBERME removal; only the answer and the session
     * cookie are left to do.
     */
    public function onLogout(LogoutEvent $event): void
    {
        $response = new JsonResponse(['message' => 'Logged out']);
        $response->headers->clearCookie($this->sessionCookieName, '/', $this->domain ?: null, $this->secure, true, $this->sameSite);
        $event->setResponse($response);
    }

    /**
     * Symfony reads the third part of a REMEMBERME cookie as an int: a cookie with anything else
     * there ends in a TypeError (500). Such a cookie can never be valid, so ignore it: 401, not 500.
     */
    public function dropMalformedRememberMe(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $value = (string) $request->cookies->get('REMEMBERME', '');
        if (str_contains($value, ':') && !preg_match('/^[^:]*:[^:]*:\d+:/', $value)) {
            $request->cookies->remove('REMEMBERME');
        }
    }
}
