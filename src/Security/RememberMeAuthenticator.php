<?php


namespace App\Security;

use App\Entity\User;
use EternalTwinClient\Object\User as ETwinUser;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class RememberMeAuthenticator extends AbstractGuardAuthenticator
{
    private UrlGeneratorInterface $url_generator;
    private Security $security;

    public function __construct(
        UrlGeneratorInterface $router, Security $security
    )
    {
        $this->url_generator = $router;
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if (!$request->isXmlHttpRequest())
            return new RedirectResponse($this->url_generator->generate('app_web_framework'));

        $intent = $request->headers->get('X-Requested-With', 'UndefinedIntent');
        switch ($intent) {
            case 'WebNavigation':
                return new RedirectResponse($this->url_generator->generate('public_login'));
            default:
                return new JsonResponse( ['error' => 'not_authorized'] );
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request)
    {
        return $request->cookies->has('myhordes_remember_me') && !$request->cookies->has('myhordes_session_id');
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->cookies->get('myhordes_remember_me', null),
            'token_on' => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $credentials['token'] ? $userProvider->loadUserByUsername( "tkn::{$credentials['token']}" ) : null;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        /** @var User $user */
        return $credentials['token_on'];
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $r = new RedirectResponse( $request->getRequestUri() );
        $r->headers->clearCookie('myhordes_remember_me');
        return $r;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        $r = new RedirectResponse( $request->getRequestUri() );
        return $r;
    }

    /**
     * @inheritDoc
     */
    public function supportsRememberMe()
    {
        return false;
    }
}