<?php


namespace App\Security;


use App\Entity\RememberMeTokens;
use App\Entity\User;
use App\Service\JSONRequestParser;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AjaxAuthenticator extends RememberMeSupportingAuthenticator implements AuthenticationEntryPointInterface
{

    private JSONRequestParser $request_parser;
    private UrlGeneratorInterface $url_generator;

    public function __construct(
        JSONRequestParser $parser,
        UrlGeneratorInterface $router,
        EntityManagerInterface $em
    )
    {
        parent::__construct($em);
        $this->request_parser = $parser;
        $this->url_generator = $router;
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if (!$request->isXmlHttpRequest())
            return new RedirectResponse($this->url_generator->generate('initial_landing'));

        $intent = $request->headers->get('X-Request-Intent', 'UndefinedIntent');
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
    public function supports(Request $request): bool
    {
        return
            $request->isXmlHttpRequest() &&
            $this->request_parser->has_all( ['login_user','login_pass'], true);
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
        if ($this->request_parser->get('remember_me', false))
            return $this->enableRememberMe( $request, $token );

        return null;
    }


    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge( "myh::{$this->request_parser->trimmed('login_user', null)}" ),
            new PasswordCredentials( $this->request_parser->trimmed('login_pass', null) )
        );
    }
}