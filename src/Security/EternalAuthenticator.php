<?php


namespace App\Security;

use App\Entity\RememberMeTokens;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EternalTwinClient\Object\User as ETwinUser;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;

class EternalAuthenticator extends RememberMeSupportingAuthenticator
{
    private UrlGeneratorInterface $url_generator;

    public function __construct(
        UrlGeneratorInterface $router,
        EntityManagerInterface $em
    )
    {
        parent::__construct($em);
        $this->url_generator = $router;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request): bool
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
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $intent = $request->headers->get('X-Request-Intent', 'UndefinedIntent');
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
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $rm_enabled = $request->getSession()->get('_etwin_rm', false);
        $request->getSession()->remove('_etwin_user');
        $request->getSession()->remove('_etwin_login');
        $request->getSession()->remove('_etwin_local');
        $request->getSession()->remove('_etwin_rm');

        if ($rm_enabled)
            return $this->enableRememberMe($request, $token);


        return null;
    }

    public function authenticate(Request $request): PassportInterface
    {
        /** @var ETwinUser $etwin_user_object */
        $etwin_user_object = $request->getSession()->get('_etwin_user');

        return new Passport(
            new UserBadge( "etwin::{$etwin_user_object->getID()}" ),
            new CustomCredentials( function($credentials, User $user) {
                return $user->getEternalID() === $credentials;
            }, $etwin_user_object->getID() )
        );
    }
}