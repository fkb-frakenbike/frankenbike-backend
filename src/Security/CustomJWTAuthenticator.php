<?php

namespace App\Security;
use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class CustomJWTAuthenticator extends AbstractAuthenticator
{
    # this can be avoided because my user badge passed as an argument just has one argument so this part down here is done automatically, but still:
    private UserRepository $userRepository;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;


    public function __construct(UserRepository $userRepository, RouterInterface $router, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return ($request->getPathInfo() === '/api/login' && $request->isMethod('POST'));
    }
    
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response('Authentication Required', Response::HTTP_UNAUTHORIZED);
    }


    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {

        return new Response('Authentication Failed: '.$exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    public function authenticate(Request $request): Passport //the authenticate is expected when the user sends the login form
    {
        // 1) Decode JSON
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            throw new \Exception('Invalid JSON.');
        }

        // 2) Extract & validate
        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;
        if (!\is_string($email) || !\is_string($password)) {
            throw new Exception('Email and password are required.');
        }

        // 3) Build the Passport
        return new Passport(
            new UserBadge($email, fn(string $userIdentifier) => 
                $this->userRepository->findOneBy(['email' => $userIdentifier]) 
                ?: throw new Exception('User not found.')
            ),
            new CustomCredentials(
                fn($plain, User $user) => $this->passwordHasher->isPasswordValid($user, $plain),
                $password
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {

        #TO DO ###############################
        // Return a 401 JSON response. By default Lexik returns "Invalid JWT Token"&#8203;:contentReference[oaicite:1]{index=1}
        return new RedirectResponse($this->router->generate('list_users')) ;
    }
    // (You can also override onAuthenticationSuccess or other methods if needed)
}
