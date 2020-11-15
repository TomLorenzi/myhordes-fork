<?php

namespace App\Controller;

use App\Entity\AntiSpamDomains;
use App\Controller\Soul\SoulController;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RegistrationLog;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Exception\DynamicAjaxResetException;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EternalTwinHandler;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User|null getUser
 */
class PublicController extends AbstractController
{
    protected $entity_manager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    protected function addDefaultTwigArgs( ?array $data = null ): array {
        $data = $data ?? [];

        $deadCitizenCount = count($this->entity_manager->getRepository(Citizen::class)->findBy(['alive' => 0]));
        
        $pictoKillZombies = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_killz_#00']);
        $zombiesKilled = $this->entity_manager->getRepository(Picto::class)->countPicto($pictoKillZombies);

        $pictoCanibal = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_cannib_#00']);
        $canibalismCount = $this->entity_manager->getRepository(Picto::class)->countPicto($pictoCanibal);

        $data['deadCitizenCount'] = $deadCitizenCount;
        $data['zombiesKilled'] = $zombiesKilled;
        $data['canibalismCount'] = $canibalismCount;

        return $data;
    }

    /**
     * @Route("jx/public/login", name="public_login")
     * @param ConfMaster $conf
     * @param EternalTwinHandler $etwin
     * @return Response
     */
    public function login(ConfMaster $conf, EternalTwinHandler $etwin): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));

        $global = $conf->getGlobalConf();
        $allow_dual_stack = $global->get(MyHordesConf::CONF_ETWIN_DUAL_STACK, true);

        return $this->render(  $etwin->isReady() ? 'ajax/public/login.html.twig' : 'ajax/public/login_legacy.html.twig', $this->addDefaultTwigArgs([
            'etwin' => $etwin->isReady(),
            'myh' => $allow_dual_stack,
        ]) );
    }

    /**
     * @Route("jx/public/register", name="public_register")
     * @param EternalTwinHandler $etwin
     * @return Response
     */
    public function register(EternalTwinHandler $etwin): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));

        if ($etwin->isReady())
            return $this->redirect($this->generateUrl('public_login'));

        return $this->render( 'ajax/public/register.html.twig',  $this->addDefaultTwigArgs() );
    }

    /**
     * @Route("jx/public/validate", name="public_validate")
     * @return Response
     */
    public function validate(): Response
    {
        if ($this->isGranted( 'ROLE_USER' ))
            return $this->redirect($this->generateUrl('initial_landing'));
        return $this->render( 'ajax/public/validate.html.twig' );
    }

    /**
     * @Route("jx/public/reset/{pkey}", name="public_reset")
     * @param string|null $pkey
     * @return Response
     */
    public function reset_pw(?string $pkey = null): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));

        /** @var UserPendingValidation|null $pending */
        $pending = null;
        if ($pkey) $pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByToken($pkey, UserPendingValidation::ResetValidation);

        return $pending
            ? $this->render( 'ajax/public/passreset.html.twig', ['mail' => $pending->getUser()->getEmail(), 'pkey' => $pkey] )
            : $this->render( 'ajax/public/passreset.html.twig', ['mail' => ''] );
    }

    /**
     * @Route("api/public/reset", name="api_reset")
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @return Response
     */
    public function reset_api(
        JSONRequestParser $parser,
        TranslatorInterface $translator,
        UserFactory $factory
    ): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (!$parser->valid()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$parser->has( 'mail', true ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($parser->has('pkey', true)) {
            if (!$parser->has_all( ['pass1','pass2'], true ))
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
                'mail' => new Constraints\NotBlank(), 'pkey' => new Constraints\NotBlank(),
                'pass1' => new Constraints\Length(
                    ['min' => 6, 'minMessage' => $translator->trans('Dein Passwort muss mindestens {{ limit }} Zeichen umfassen.', [], 'login')]),
                'pass2' => new Constraints\EqualTo(
                    ['value' => $parser->trimmed( 'pass1' ), 'message' => $translator->trans('Die eingegebenen Passwörter stimmen nicht überein.', [], 'login')]),
            ]) );

            if ($violations->count() === 0) {

                $factory->resetUserPassword(
                    $parser->trimmed('mail'),
                    $parser->trimmed('pkey'),
                    $parser->trimmed('pass1'),
                    $error
                );

                switch ($error) {
                    case UserFactory::ErrorNone:
                        return AjaxResponse::success( );
                    case UserFactory::ErrorInvalidParams: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                    default: return AjaxResponse::error($error);
                }

            } else {
                $v = [];
                foreach ($violations as &$violation)
                    /** @var ConstraintViolationInterface $violation */
                    $v[] = $violation->getMessage();

                return AjaxResponse::error( 'invalid_fields', ['fields' => $v] );
            }

        } else {
            $user = $factory->prepareUserPasswordReset(
                $parser->trimmed('mail'),
                $error
            );

            if($user && $error === UserFactory::ErrorNone) {
                try {
                    $this->entity_manager->persist($user);
                    $this->entity_manager->flush();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }
            } elseif ($error === UserFactory::ErrorInvalidParams) {
                return AjaxResponse::success( 'validate' );
            } else {
                return AjaxResponse::error($error);
            }

            return AjaxResponse::success( 'validate' );
        }
    }

    /**
     * @Route("api/public/register", name="api_register")
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function register_api(
        Request $request,
        ConfMaster $conf,
        JSONRequestParser $parser,
        TranslatorInterface $translator,
        UserFactory $factory,
        EntityManagerInterface $entityManager,
        EternalTwinHandler $etwin
    ): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ) || $etwin->isReady())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (!$parser->valid()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$parser->has_all( ['user','mail1','mail2','pass1','pass2'], true ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (in_array($parser->trimmed('user', ''), ['Der Rabe','DerRabe','Der_Rabe','DerRaabe']))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
            'user' => [
                new Constraints\Regex( ['match' => false, 'pattern' => '/[\s$<>]/', 'message' => $translator->trans('Dein Name kann keine Leerzeichen sowie "$", "<" und ">" enthalten.', [], 'login') ] ),
                new Constraints\Length(
                    ['min' => 4, 'max' => 16,
                        'minMessage' => $translator->trans('Dein Name muss mindestens {{ limit }} Zeichen umfassen.', [], 'login'),
                        'maxMessage' => $translator->trans('Dein Name kann höchstens {{ limit }} Zeichen umfassen.', [], 'login'),
                    ]),
            ],
            'mail1' => [
                new Constraints\Email( ['message' => $translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login')]),
                new Constraints\Callback( [ 'callback' => function(string $mail, ExecutionContextInterface $context) use ($parser,$entityManager,$translator) {
                    $parts = explode('@', $mail, 2);
                    if (count($parts) < 2) return;
                    $parts = explode('.', $parts[1]);

                    $repo = $entityManager->getRepository(AntiSpamDomains::class);
                    $test = '';
                    while (!empty($parts)) {
                        $d = array_pop($parts);
                        if (empty($d)) continue;
                        $test = $d . (empty($test) ? '' : ".{$test}");
                        if ($repo->findOneBy(['domain' => $test])) {
                            $context->buildViolation($translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login'))
                                ->atPath('mail1')
                                ->addViolation();
                        }
                    }
                } ] )
                ],
            'mail2' => new Constraints\EqualTo(
                ['value' => $parser->trimmed( 'mail1'), 'message' => $translator->trans('Die eingegebenen E-Mail Adressen stimmen nicht überein.', [], 'login')]),
            'pass1' => new Constraints\Length(
                ['min' => 6, 'minMessage' => $translator->trans('Dein Passwort muss mindestens {{ limit }} Zeichen umfassen.', [], 'login')]),
            'pass2' => new Constraints\EqualTo(
                ['value' => $parser->trimmed( 'pass1' ), 'message' => $translator->trans('Die eingegebenen Passwörter stimmen nicht überein.', [], 'login')]),
            //'privacy' => new Constraints\IsTrue(),
        ]) );

        if ($violations->count() === 0) {

            if ($entityManager->getRepository(RegistrationLog::class)->countRecentRegistrations($request->getClientIp()) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_REG, 2))
                return AjaxResponse::error(UserFactory::ErrorTooManyRegistrations);

            $user = $factory->createUser(
                $parser->trimmed('user'),
                $parser->trimmed('mail1'),
                $parser->trimmed('pass1'),
                false,
                $error
            );

            switch ($error) {
                case UserFactory::ErrorNone:
                    try {

                        $entityManager->persist( (new RegistrationLog())
                            ->setUser($user)
                            ->setDate(new \DateTime())
                            ->setIdentifier( md5($request->getClientIp()) )
                        );

                        $entityManager->persist( $user );
                        $entityManager->flush();
                    } catch (Exception $e) {
                        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
                    }
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    $this->get('security.token_storage')->setToken($token);

                    return AjaxResponse::success( 'validation');

                case UserFactory::ErrorInvalidParams: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                default: return AjaxResponse::error($error);
            }

        } else {
            $v = [];
            foreach ($violations as &$violation)
                /** @var ConstraintViolationInterface $violation */
                $v[] = $violation->getMessage();

            return AjaxResponse::error( 'invalid_fields', ['fields' => $v] );
        }
    }

    /**
     * @Route("jx/public/login/etwin/{code}", name="etwin_login")
     * @param string $code
     * @param TranslatorInterface $translator
     * @param EternalTwinHandler $etwin
     * @param SessionInterface $session
     * @return Response
     */
    public function login_via_etwin(string $code, TranslatorInterface $translator, EternalTwinHandler $etwin, SessionInterface $session): Response {


        $myhordes_user = $this->getUser();
        if ($myhordes_user && $myhordes_user->getEternalID())
            throw new DynamicAjaxResetException(Request::createFromGlobals());

        if (empty($code) || !$etwin->isReady()) {
            $this->addFlash('error', $translator->trans('Fehler bei der Datenübertragung.', [], 'login'));
            throw new DynamicAjaxResetException(Request::createFromGlobals());
        }

        try {
            $etwin->setAuthorizationCode( $code );
            $user = $etwin->requestAuthSelf($e);
        } catch (Exception $e) {
            $this->addFlash('error', $translator->trans('Fehler bei der Datenübertragung.', [], 'login'));
            throw new DynamicAjaxResetException(Request::createFromGlobals());
        }

        if ($user->isValid()) {

            $potential_user = $this->entity_manager->getRepository(User::class)->findOneByEternalID( $user->getID() );

            $session->set('_etwin_user', $user);
            $session->set('_etwin_login', $potential_user !== null && $myhordes_user === null);
            $session->set('_etwin_local', $myhordes_user ? $myhordes_user->getId() : null);

            return $this->render( 'ajax/public/et_welcome.html.twig', [
                'etwin_user' => $user,
                'target_user' => $potential_user,
                'current_user' => $myhordes_user
            ] );


        } else $this->addFlash('error', $translator->trans('Fehler bei der Datenübertragung.', [], 'login'));

        die;
    }

    /**
     * @Route("api/public/etwin/confirm", name="api_etwin_confirm")
     * @param Request $request
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @param UserFactory $userFactory
     * @param UserPasswordEncoderInterface $pass
     * @param TranslatorInterface $trans
     * @param ConfMaster $conf
     * @return Response
     */
    public function etwin_confirm_api(Request $request, JSONRequestParser $parser, SessionInterface $session, UserFactory $userFactory,
                                      UserPasswordEncoderInterface $pass, TranslatorInterface $trans, ConfMaster $conf): Response {

        $myhordes_user = $this->getUser();
        $password = $parser->get('pass', null);

        /** @var \EternalTwinClient\Object\User $etwin_user */
        $etwin_user = $session->get('_etwin_user', null);

        if ($etwin_user !== null && !is_a($etwin_user, \EternalTwinClient\Object\User::class))
            return new RedirectResponse($this->generateUrl( 'api_etwin_cancel' ));

        // Case A - Login
        if ($myhordes_user === null && $session->has('_etwin_user') && $session->get('_etwin_login', false)) {
            /** @var User $myhordes_user */
            $myhordes_user = $this->entity_manager->getRepository(User::class)->findOneByEternalID( $etwin_user->getID() );

            // Update display name
            if ($myhordes_user) {
                $this->entity_manager->persist( $myhordes_user->setDisplayName( $myhordes_user->getUsername() === $etwin_user->getDisplayName() ? null : $etwin_user->getDisplayName() ) );

                try {
                    $this->entity_manager->flush();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

                return new RedirectResponse($this->generateUrl( 'api_login' ));

            } else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        }

        // Case B - Account Creation
        elseif ($myhordes_user === null && $session->has('_etwin_user') && !$session->get('_etwin_login', false)) {

            if ($this->entity_manager->getRepository(RegistrationLog::class)->countRecentRegistrations($request->getClientIp()) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_REG, 2))
                return AjaxResponse::error(UserFactory::ErrorTooManyRegistrations);

            $new_user = $userFactory->importUser( $etwin_user );
            $this->entity_manager->persist( $new_user );

            try {
                $this->entity_manager->persist( (new RegistrationLog())
                    ->setUser($new_user)
                    ->setDate(new \DateTime())
                    ->setIdentifier( md5($request->getClientIp()) )
                );

                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            $session->set('_etwin_login', true);
            return new RedirectResponse($this->generateUrl( 'api_login' ));
        }

        // Case C - Account linking
        elseif ($myhordes_user !== null && $session->has('_etwin_user') && $session->get('_etwin_local') === $myhordes_user->getId()) {

            if ($myhordes_user->getEternalID()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            if (empty($password) || !$pass->isPasswordValid( $myhordes_user, $password ))
                return AjaxResponse::error( SoulController::ErrorUserEditPasswordIncorrect );

            if ($this->entity_manager->getRepository(User::class)->findOneByEternalID( $etwin_user->getID() ))
                return AjaxResponse::error( SoulController::ErrorETwinImportProfileInUse );

            $myhordes_user->setEternalID( $etwin_user->getID() )->setPassword(null);
            if ($etwin_user->getDisplayName() !== $myhordes_user->getUsername())
                $myhordes_user->setDisplayName( $etwin_user->getDisplayName() );

            $this->entity_manager->persist($myhordes_user);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
            }

            $session->set('_etwin_login', true);

            $this->addFlash('success', $trans->trans('Dein Account wurde erfolgreich verknüpft!', [], 'login'));

            return new RedirectResponse($this->generateUrl( 'api_login' ));
        }

        return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
    }

    /**
     * @Route("api/public/etwin/cancel", name="api_etwin_cancel")
     * @param SessionInterface $session
     * @return Response
     */
    public function etwin_cancel_api(SessionInterface $session): Response {
        $session->remove('_etwin_user');
        $session->remove('_etwin_login');
        return AjaxResponse::success();
    }

    /**
     * @Route("api/public/login", name="api_login")
     * @return Response
     */
    public function login_api(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return new AjaxResponse( ['success' => false ] );

        // If there is an open password reset validation, a successful login closes it
        $reset_validation = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user, UserPendingValidation::ResetValidation);
        if ($reset_validation) try {

            $this->entity_manager->remove($reset_validation);
            $user->setPendingValidation(null);
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

        } catch(Exception $e) {}

        if (!$user->getValidated())
            return new AjaxResponse( ['success' => true, 'require_validation' => true ] );
        else return new AjaxResponse( ['success' => true, 'require_validation' => false ] );
    }

    /**
     * @Route("api/public/validate", name="api_validate")
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @return Response
     */
    public function validate_api(
        JSONRequestParser $parser,
        TranslatorInterface $translator,
        UserFactory $factory
    ): Response
    {
        if (!$parser->valid()) return new AjaxResponse( ['error' => ErrorHelper::ErrorInvalidRequest] );
        if (!$parser->has_all( ['validate'], true ))
            return new AjaxResponse( ['error' => ErrorHelper::ErrorInvalidRequest] );

        $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
            'validate'  => new Constraints\Length(
                ['min' => 16, 'max' => 16,
                    'exactMessage' => $translator->trans('Der Validierungscode muss {{ limit }} Zeichen umfassen.', [], 'login'),
                ])
        ]) );

        if ($violations->count() === 0) {

            /** @var User $user */
            $user = $this->getUser();

            if ($factory->validateUser( $user, $parser->trimmed('validate'), $error )) {

                if ($this->getUser()) {
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    $this->get('security.token_storage')->setToken($token);
                }

                return new AjaxResponse( ['success' => true] );
            } else switch ($error) {
                case UserFactory::ErrorDatabaseException: return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                default: return AjaxResponse::error($error);
            }

        } else {
            $v = [];
            foreach ($violations as &$violation)
                /** @var ConstraintViolationInterface $violation */
                $v[] = $violation->getMessage();

            return AjaxResponse::error( 'invalid_fields', ['fields' => $v] );
        }
    }

    /**
     * @Route("jx/public/welcome", name="public_welcome")
     * @return Response
     */
    public function welcome(): Response
    {
        return $this->render('ajax/public/intro.html.twig', $this->addDefaultTwigArgs());
    }

    /**
     * @Route("jx/public/privacy", name="public_privacy")
     * @return Response
     */
    public function privacy(): Response
    {
        return $this->render('ajax/public/privacy.html.twig', $this->addDefaultTwigArgs());
    }

}
