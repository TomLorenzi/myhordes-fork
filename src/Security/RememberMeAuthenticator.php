<?php


namespace App\Security;

use App\Entity\User;
use EternalTwinClient\Object\User as ETwinUser;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class RememberMeAuthenticator extends AbstractAuthenticator
{
    /**
     * @inheritDoc
     */
    public function supports(Request $request): bool
    {
        return $request->cookies->has('myhordes_remember_me') && !$request->cookies->has('myhordes_session_id');
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $r = new RedirectResponse( $request->getRequestUri() );
        $r->headers->clearCookie('myhordes_remember_me');
        return $r;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return new RedirectResponse( $request->getRequestUri() );
    }

    public function authenticate(Request $request): PassportInterface
    {
        return new SelfValidatingPassport(
            new UserBadge( "tkn::{$request->cookies->get('myhordes_remember_me', null)}" ),
        );
    }
}