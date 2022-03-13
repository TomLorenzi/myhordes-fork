<?php


namespace App\Security;

use App\Entity\RememberMeTokens;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;

abstract class RememberMeSupportingAuthenticator extends AbstractAuthenticator
{
    protected EntityManagerInterface $em;

    public function __construct( EntityManagerInterface $em )
    {
        $this->em = $em;
    }

    protected function enableRememberMe(Request $request, TokenInterface $token): ?Response {
        /** @var User $user */
        $user = $token->getUser();

        if ( !($existing_token = $this->em->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user])) ) {

            do try {
                $random = bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                return null;
            } while ( $this->em->getRepository(RememberMeTokens::class)->findOneBy(['token' => $random]) );

            $this->em->persist( $existing_token = (new RememberMeTokens())->setUser( $user )->setToken( $random ) );
            try { $this->em->flush(); } catch (\Exception $e) { return null; }
        }

        if ($existing_token) {
            $r = new RedirectResponse( $request->getRequestUri() );
            $r->headers->setCookie( new Cookie('myhordes_remember_me', $existing_token->getToken(), strtotime('now+3years'), '/', null, true, true ) );
            return $r;
        } else return null;
    }
}