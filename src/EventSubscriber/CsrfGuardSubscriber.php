<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Anti-CSRF without a synchronised token. Cookies are SameSite=Lax; on top, every unsafe
 * /api request must carry a custom header (a cross-site page cannot send it without a CORS
 * preflight, which only allowed origins pass) and, when the browser sends an Origin, that
 * Origin must be allowed. Works for JSON and multipart bodies alike.
 */
class CsrfGuardSubscriber implements EventSubscriberInterface
{
    public const HEADER = 'X-Requested-With';

    /** @param string $allowedOriginRegex same pattern as the CORS allow-list (no delimiters) */
    public function __construct(private string $allowedOriginRegex)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // after the router (32), before the firewall (8)
        return [KernelEvents::REQUEST => ['onRequest', 16]];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api/') || $request->isMethodSafe()) {
            return;
        }

        if (!$request->headers->get(self::HEADER)) {
            $event->setResponse($this->reject('Missing '.self::HEADER.' header'));

            return;
        }

        $origin = $request->headers->get('Origin');
        if (null !== $origin && !preg_match('{'.$this->allowedOriginRegex.'}', $origin)) {
            $event->setResponse($this->reject('Origin not allowed'));
        }
    }

    private function reject(string $reason): JsonResponse
    {
        return new JsonResponse(['error' => $reason], Response::HTTP_FORBIDDEN);
    }
}
