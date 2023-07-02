<?php


namespace App\Security;

use App\Service\Actions\Security\GenerateKeyAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class RememberMeAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly Security $security,
        private readonly GenerateKeyAction $keygen
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(Request $request): bool
    {
        return $request->cookies->has('myhordes_remember_me') && !$this->security->getUser()?->getUserIdentifier();
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
        $request->getSession()->set('token', ($this->keygen)(15));
        if ($request->isXmlHttpRequest() && str_starts_with( $request->getPathInfo(), '/jx/' ))
            return new Response( '', 200, [
                'X-AJAX-Control' => 'navigate',
                'X-AJAX-Navigate' => "{$request->getScheme()}://{$request->getHost()}{$request->getPathInfo()}"
            ] );

        else return new RedirectResponse( $request->getRequestUri() );
    }

    public function authenticate(Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge( "tkn::{$request->cookies->get('myhordes_remember_me', null)}" ),
        );
    }
}