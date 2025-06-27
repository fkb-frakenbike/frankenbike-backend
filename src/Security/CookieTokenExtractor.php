<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieTokenExtractor implements TokenExtractorInterface
{
    private string $cookieName;

    public function __construct(string $cookieName = 'AUTH_TOKEN_COOKIE')
    {
        $this->cookieName = $cookieName;
    }

    public function extract(Request $request): ?string
    {
        return $request->cookies->get($this->cookieName);
    }
}
