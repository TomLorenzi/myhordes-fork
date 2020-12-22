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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class AjaxAuthenticator extends AbstractGuardAuthenticator
{

    private JSONRequestParser $request_parser;
    private UserPasswordEncoderInterface $password_generator;
    private UrlGeneratorInterface $url_generator;
    private EntityManagerInterface $em;

    public function __construct(
        JSONRequestParser $parser,
        UserPasswordEncoderInterface $passwordEncoder,
        UrlGeneratorInterface $router,
        EntityManagerInterface $em
    )
    {
        $this->request_parser = $parser;
        $this->password_generator = $passwordEncoder;
        $this->url_generator = $router;
        $this->em = $em;
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
        return
            $request->isXmlHttpRequest() &&
            $this->request_parser->has_all( ['login_user','login_pass'], true);
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request)
    {
        return [
            'username' => $this->request_parser->trimmed('login_user', null),
            'password' => $this->request_parser->trimmed('login_pass', null)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername( "myh::{$credentials['username']}" );
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->password_generator->isPasswordValid( $user, $credentials['password'] );
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
        if ($this->request_parser->get('remember_me', false)) {

            /** @var User $user */
            $user = $token->getUser();

            if ( $existing_token = $this->em->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]) ) {
                $r = new RedirectResponse( $request->getRequestUri() );
                $r->headers->setCookie( new Cookie('myhordes_remember_me', $existing_token->getToken(), strtotime('now+3years'), '/', null, false, true ) );
                return $r;
            }

            do try {
                $random = bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                return null;
            } while ( $this->em->getRepository(RememberMeTokens::class)->findOneBy(['token' => $random]) );

            $this->em->persist( (new RememberMeTokens())->setUser( $user )->setToken( $random ) );

            try { $this->em->flush(); } catch (\Exception $e) { return null; }
            $r = new RedirectResponse( $request->getRequestUri() );
            $r->headers->setCookie( new Cookie('myhordes_remember_me', $random, strtotime('now+3years'), '/', null, true, true ) );
            return $r;
        }

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