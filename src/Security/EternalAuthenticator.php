<?php


namespace App\Security;

use App\Entity\User;
use EternalTwinClient\Object\User as ETwinUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class EternalAuthenticator extends AbstractGuardAuthenticator
{
    private UrlGeneratorInterface $url_generator;

    public function __construct(
        UrlGeneratorInterface $router
    )
    {
        $this->url_generator = $router;
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
        if (!$request->isXmlHttpRequest() ||
            !$request->getSession()->has('_etwin_user') ||
            !$request->getSession()->get('_etwin_login', false))
            return false;

        /** @var ETwinUser $etwin_user_object */
        $etwin_user_object = $request->getSession()->get('_etwin_user');

        if (!is_a($etwin_user_object, ETwinUser::class) || !$etwin_user_object->isValid())
            return false;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request)
    {
        /** @var ETwinUser $etwin_user_object */
        $etwin_user_object = $request->getSession()->get('_etwin_user');

        return [
            'etwin_id' => $etwin_user_object->getID(),
            'etwin_on' => $request->getSession()->has('_etwin_login')
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $credentials['etwin_on'] ? $userProvider->loadUserByUsername( "etwin::{$credentials['etwin_id']}" ) : null;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        /** @var User $user */
        return $credentials['etwin_on'] && $user->getEternalID() === $credentials['etwin_id'];
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $intent = $request->headers->get('X-Requested-With', 'UndefinedIntent');
        switch ($intent) {
            case 'WebNavigation':
                return new RedirectResponse($this->url_generator->generate('public_login'));
            default:
                return new JsonResponse( ['success' => false, 'message' => $exception->getMessage()] );
        }
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        $request->getSession()->remove('_etwin_user');
        $request->getSession()->remove('_etwin_login');
        $request->getSession()->remove('_etwin_local');
        return null;
    }

    /**
     * @inheritDoc
     */
    public function supportsRememberMe()
    {
        return false;
    }
}