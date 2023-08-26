<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\UserPendingValidation;
use App\Service\Actions\EMail\GetEMailDomainAction;
use App\Service\Actions\Security\GenerateKeyAction;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


/**
 * @Route("/rest/v1/user/security", name="rest_user_security_", condition="request.headers.get('Accept') === 'application/json'")
 * @GateKeeperProfile("skip")
 */
class SecurityController extends CustomAbstractCoreController
{

    /**
     * @Route("/token/exchange/{ticket}", name="token_exchange", methods={"GET"})
     * @param string $ticket
     * @param Request $request
     * @param GenerateKeyAction $keygen
     * @return JsonResponse
     */
    public function find(string $ticket, Request $request, GenerateKeyAction $keygen): JsonResponse {
        $tokens = $request->getSession()->get('token-ticket');
        if ($ticket && isset($tokens[$ticket])) {
            $token = $tokens[$ticket];
            unset($tokens[$ticket]);
            $request->getSession()->set('token-ticket', $tokens);
        } else $token = null;

        if ($token === null) $token = $keygen(16);

        return new JsonResponse(['token' => $token]);
    }

    /**
     * @Route("/account/local/create", name="add_local_login", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $pw_enc
     * @param GenerateKeyAction $keygen
     * @param GetEMailDomainAction $domainAction
     * @param MailerInterface $mailer
     * @param TranslatorInterface $trans
     * @param Environment $twig
     * @return JsonResponse
     */
    public function add_local_login(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $pw_enc,
        GenerateKeyAction $keygen,
        GetEMailDomainAction $domainAction,
        MailerInterface $mailer,
        TranslatorInterface $trans,
        Environment $twig
    ): JsonResponse {
        $user = $this->getUser();
        if ($user->getPassword() !== null) return new JsonResponse([], Response::HTTP_CONFLICT);

        $new_password = $keygen(8) . '-' . $keygen(8);

        $from_domain = $domainAction();
        $headline = $trans->trans('Passwort-Login aktivieren', [], 'soul');

        try {
            $mailer->send((new Email())
                              ->from( "The Undead Mailman <mailzombie@{$from_domain}>" )
                              ->to( $user->getEmail() )
                              ->subject( "MyHordes - $headline" )
                              ->html( $twig->render( 'mail/local_account.html.twig', [
                                  'title' => $headline,
                                  'user' => $user,
                                  'password' => $new_password
                              ] ) )
            );

            $user->setPassword($pw_enc->hashPassword($user, $new_password));
            $em->persist($user);
            $em->flush();

        } catch (\Throwable $t) {
            return new JsonResponse([ 'success' => false, 'message' => $t->getMessage() ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => $trans->trans('Dein neues Passwort wurde dir per E-Mail zugeschickt.', [], 'soul')
        ]);
    }
}
