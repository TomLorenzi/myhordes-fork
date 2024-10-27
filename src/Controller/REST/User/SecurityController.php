<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AccountRestriction;
use App\Entity\RememberMeTokens;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\Actions\EMail\GetEMailDomainAction;
use App\Service\Actions\Security\GenerateKeyAction;
use App\Service\Actions\Security\GenerateMercureToken;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\Locksmith;
use App\Service\RandomGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[Route(path: '/rest/v1/user/security', name: 'rest_user_security_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile('skip')]
class SecurityController extends CustomAbstractCoreController
{

    /**
     * @param string $ticket
     * @param Request $request
     * @param GenerateKeyAction $keygen
     * @param Locksmith $locksmith
     * @param TagAwareCacheInterface $gameCachePool
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route(path: '/token/exchange/{ticket}', name: 'token_exchange', methods: ['GET'])]
    public function find(string $ticket, Request $request, GenerateKeyAction $keygen, Locksmith $locksmith, TagAwareCacheInterface $gameCachePool): JsonResponse {
        $lock = $locksmith->waitForLock("ticketing_{$request->getSession()->getId()}");

        $valid = $gameCachePool->get( "ticketing_{$ticket}", function (ItemInterface $item) use ($ticket, $request, $keygen) {
            $item->expiresAfter(0);
            return false;
        } );
        $gameCachePool->delete("ticketing_{$ticket}");
        $lock->release();

        return new JsonResponse(['token' => $valid ? $request->getSession()->get('token', ($keygen)(16)) : ($keygen)(16)]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $token
     * @param UserPasswordHasherInterface $pw_enc
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    #[Route(path: '/account', name: 'delete_account', methods: ['DELETE'])]
    #[IsGranted('ROLE_NATURAL')]
    public function delete_account(
        EntityManagerInterface $em,
        TokenStorageInterface $token,
        UserPasswordHasherInterface $pw_enc,
        JSONRequestParser $parser
    ): JsonResponse {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY') || $user->getPassword() === null)
            return new JsonResponse([], Response::HTTP_CONFLICT);

        if (!$pw_enc->isPasswordValid( $user, $parser->trimmed('pw') ))
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('Dein eingegebenes Passwort ist nicht korrekt. Du benötigst dein aktuelles Passwort, um deinen Account zu bearbeiten.', [], 'soul')
            ]);

        $name = $user->getUsername();
        $user->setDeleteAfter( new DateTime('+24hour') );
        $user->setCheckInt($user->getCheckInt() + 1);

        if ($rm_token = $em->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]))
            $em->remove($rm_token);

        $em->flush();
        $token->setToken(null);

        return new JsonResponse([
            'success' => true,
            'message' =>
                $this->translator->trans('Auf wiedersehen, {name}. Wir werden dich vermissen und hoffen, dass du vielleicht doch noch einmal zurück kommst.', ['{name}' => $name], 'login') . '<br/>' .
                $this->translator->trans('Du wirst nun ausgeloggt. Dein Account wird in 24-48 Stunden gelöscht. Bis dahin kannst du dich jederzeit erneut einloggen und dadurch die Löschung deines Accounts abbrechen.', [], 'soul')
        ]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $pw_enc
     * @param GenerateKeyAction $keygen
     * @param GetEMailDomainAction $domainAction
     * @param MailerInterface $mailer
     * @param TranslatorInterface $trans
     * @param Environment $twig
     * @return JsonResponse
     */
    #[Route(path: '/account/local/create', name: 'add_local_login', methods: ['POST'])]
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


    #[Route(path: '/renew-token', name: 'renew_core_token', methods: ['GET'])]
    public function renew_token(GenerateMercureToken $token): JsonResponse
    {
        return new JsonResponse(
            ['token' => ($token)(
                renew_url: $this->generateUrl('rest_user_security_renew_core_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            )]
        );
    }
}
