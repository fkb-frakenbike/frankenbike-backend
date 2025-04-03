<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CustomJWTAuthenticator extends JWTAuthenticator
{


    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {

        // Return a 401 JSON response. By default Lexik returns "Invalid JWT Token"&#8203;:contentReference[oaicite:1]{index=1}
        return parent::onAuthenticationFailure($request, $exception);
    }

    // (You can also override onAuthenticationSuccess or other methods if needed)
}
