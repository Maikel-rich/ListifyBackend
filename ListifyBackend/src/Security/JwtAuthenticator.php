<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JwtAuthenticator extends AbstractAuthenticator
{
    private JWTEncoderInterface $jwtEncoder;
    private UserProviderInterface $userProvider;

    public function __construct(JWTEncoderInterface $jwtEncoder, UserProviderInterface $userProvider)
    {
        $this->jwtEncoder = $jwtEncoder;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): bool
    {
        if ($request->attributes->get('_route') === 'api_login') {
            return false; // No se usa JWT en el login
        }

        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('Authorization');

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No JWT token provided');
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $decodedToken = $this->jwtEncoder->decode($token);

            if (!isset($decodedToken['username'])) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT structure');
            }

            return new Passport(
                new UserBadge($decodedToken['username']),
                new CustomCredentials(fn($credentials) => true, $token)
            );


        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid JWT Token');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }
}
