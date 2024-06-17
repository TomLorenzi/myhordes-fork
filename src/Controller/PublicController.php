<?php

namespace App\Controller;

use App\Entity\Announcement;
use App\Entity\AntiSpamDomains;
use App\Controller\Soul\SoulController;
use App\Entity\Changelog;
use App\Entity\HeaderStat;
use App\Entity\HordesFact;
use App\Entity\MarketingCampaign;
use App\Entity\MarketingCampaignConversion;
use App\Entity\RegistrationLog;
use App\Entity\RegistrationToken;
use App\Entity\Season;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Entity\UserReferLink;
use App\Entity\UserSponsorship;
use App\Enum\DomainBlacklistType;
use App\Exception\DynamicAjaxResetException;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EternalTwinHandler;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User|null getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
class PublicController extends CustomAbstractController
{
	protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array {
        $data = parent::addDefaultTwigArgs($section, $data);

        $headerStat = $this->entity_manager->getRepository(HeaderStat::class)->findOneBy([], ['timestamp' => 'DESC']);
        $data['deadCitizenCount'] = $headerStat?->getKilledCitizens() ?? 0;
        $data['zombiesKilled'] = $headerStat?->getKilledZombies() ?? 0;
        $data['canibalismCount'] = $headerStat?->getCannibalismActs() ?? 0;

        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
        if ($locale) $locale = explode('_', $locale)[0];
        if (!in_array($locale, $this->generatedLangsCodes)) $locale = null;

        $facts = $this->entity_manager->getRepository(HordesFact::class)->findBy(['lang' => $locale ?? 'de']);
        shuffle($facts);

        $data['fact'] = $facts[0];

        return $data;
    }

    /**
     * @param ConfMaster $conf
     * @param EternalTwinHandler $etwin
     * @return Response
     */
    #[Route(path: 'jx/public/login', name: 'public_login')]
    public function login(ConfMaster $conf, EternalTwinHandler $etwin): Response
    {
        $rt = Request::createFromGlobals()->headers->get('X-Render-Target');
        if ($rt && $rt !== 'content' ) throw new DynamicAjaxResetException(Request::createFromGlobals());

        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));

        $global = $conf->getGlobalConf();
        $allow_dual_stack = $global->get(MyHordesConf::CONF_ETWIN_DUAL_STACK, true);

        return $this->render(  $etwin->isReady() ? 'ajax/public/login.html.twig' : 'ajax/public/login_legacy.html.twig', $this->addDefaultTwigArgs(null, [
            'etwin' => $etwin->isReady(),
            'myh' => $allow_dual_stack,
        ]) );
    }

    /**
     * @param EternalTwinHandler $etwin
     * @param SessionInterface $s
     * @return Response
     */
    #[Route(path: 'jx/public/register', name: 'public_register')]
    public function register(EternalTwinHandler $etwin, SessionInterface $s): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));

        if ($etwin->isReady())
            return $this->redirect($this->generateUrl('public_login'));

        return $this->render( 'ajax/public/register.html.twig',  $this->addDefaultTwigArgs(null, [
            'refer' => $s->get('refer'),
            'need_token' => $this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOKEN_NEEDED_FOR_REGISTRATION)
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/public/validate', name: 'public_validate')]
    public function validate(): Response
    {
        if ($this->getUser() && $this->getUser()->getValidated())
            return $this->redirect($this->generateUrl('initial_landing'));
        return $this->render( 'ajax/public/validate.html.twig' );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/public/accept_tos', name: 'public_accept_tos')]
    public function accept_tos(): Response
    {
        if (!$this->getUser() || ($this->getUser()->tosAccepted() && $this->getUser()->tosUpdateAccepted()))
            return $this->redirect($this->generateUrl('initial_landing'));

        return $this->render( 'ajax/public/tos_first.html.twig', [
            'allow_grace' => $this->getUser()->getTosgracenum() < 14 || ($this->getUser()->getTosgrace() !== null && $this->getUser()->getTosgrace() > new DateTime())
        ] );
    }

    /**
     * @param bool $accept
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/public/tos/accept', name: 'api_accept_tos', defaults: ['accept' => true])]
    #[Route(path: 'api/public/tos/later', name: 'api_defer_tos', defaults: ['accept' => false])]
    public function tos_api(bool $accept): Response
    {
        if (!$this->getUser() || ($this->getUser()->tosAccepted() && $this->getUser()->tosUpdateAccepted()))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (!$accept && $this->getUser()->getTosgracenum() >= 14 && ($this->getUser()->getTosgrace() === null || $this->getUser()->getTosgrace() < new DateTime()))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $user = $this->getUser();

        if ($accept) {
            $user->setTosver(1);
            $user->setTosgracenum( 0 );
            $user->setTosgrace( null );
        } elseif ($this->getUser()->getTosgrace() === null || $this->getUser()->getTosgrace() < new DateTime()) {
            $user->setTosgracenum( $user->getTosgracenum() + 1 );
            $user->setTosgrace( (new DateTime('now'))->modify('+12hour') );
        }

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);

        return AjaxResponse::success();
    }

    /**
     * @param string|null $pkey
     * @return Response
     */
    #[Route(path: 'jx/public/reset/{pkey}', name: 'public_reset')]
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
     * @param UserFactory $factory
     * @return Response
     */
    #[Route(path: 'api/public/revalidate', name: 'api_resend_validation')]
    public function revalidate_api(UserFactory $factory): Response
    {
        if (!$this->isGranted( 'ROLE_REGISTERED' ))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($this->getUser(), UserPendingValidation::EMailValidation);
        if (!$pending) {
            $factory->announceValidationToken( $factory->ensureValidation( $this->getUser(), UserPendingValidation::EMailValidation ) );
            return AjaxResponse::success();
        }

        if (($pending->getTime()->getTimestamp() + 300) > time() )
            return AjaxResponse::error(UserFactory::ErrorTooManyMails);

        $this->entity_manager->persist($pending->setTime(new \DateTime()));
        try {
            $factory->announceValidationToken( $pending );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorInternalError);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @return Response
     */
    #[Route(path: 'api/public/reset', name: 'api_reset')]
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
                    ['min' => 6, 'minMessage' => $translator->trans('Dein Passwort muss mindestens { limit } Zeichen umfassen.', [], 'login')]),
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
                    $this->entity_manager->persist($user->getPendingValidation());
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
     * @param Request $request
     * @param ConfMaster $conf
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @param EntityManagerInterface $entityManager
     * @param EternalTwinHandler $etwin
     * @param UserHandler $userHandler
     * @param SessionInterface $session
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/public/register', name: 'api_register')]
    public function register_api(
        Request $request,
        ConfMaster $conf,
        JSONRequestParser $parser,
        TranslatorInterface $translator,
        UserFactory $factory,
        EntityManagerInterface $entityManager,
        EternalTwinHandler $etwin,
        UserHandler $userHandler,
        SessionInterface $session,
    ): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ) || $etwin->isReady())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (!$parser->valid()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$parser->has_all( ['user','mail1','mail2','pass1','pass2'], false ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$userHandler->isNameValid($parser->trimmed('user', '')))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOKEN_NEEDED_FOR_REGISTRATION) && !$parser->has('token', true)) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
            'user' => [
                new Constraints\Regex( ['match' => false, 'pattern' => '/[^\w]/', 'message' => $translator->trans('Dein Name kann nur alphanumerische Zeichen enthalten.', [], 'login') ] ),
                new Constraints\Length(
                    ['min' => 4, 'max' => 16,
                        'minMessage' => $translator->trans('Dein Name muss mindestens { limit } Zeichen umfassen.', [], 'login'),
                        'maxMessage' => $translator->trans('Dein Name kann höchstens { limit } Zeichen umfassen.', [], 'login'),
                    ]),
            ],
            'mail1' => [
                new Constraints\Email( message: $translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login')),
                new Constraints\Callback( [ 'callback' => function(string $mail, ExecutionContextInterface $context) use ($translator, $userHandler) {
					if (!$userHandler->isEmailValid($mail)) {
						$context->buildViolation($translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login'))->atPath('mail1')->addViolation()
						;
					}
				} ] )
            ],
            'mail2' => new Constraints\EqualTo(
                ['value' => $parser->trimmed( 'mail1'), 'message' => $translator->trans('Die eingegebenen E-Mail Adressen stimmen nicht überein.', [], 'login')]),
            'pass1' => new Constraints\Length(
                ['min' => 6, 'minMessage' => $translator->trans('Dein Passwort muss mindestens { limit } Zeichen umfassen.', [], 'login')]),
            'pass2' => new Constraints\EqualTo(
                ['value' => $parser->trimmed( 'pass1' ), 'message' => $translator->trans('Die eingegebenen Passwörter stimmen nicht überein.', [], 'login')]),
            'tos' => new Constraints\IsTrue(message: $translator->trans('Bitte stimme der Datenschutzerklärung und den Nutzungsbedingungen zu.', [], 'login'))
        ], allowExtraFields: true, allowMissingFields: false) );

        if ($violations->count() === 0) {

            $regToken = null;

            if ($this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOKEN_NEEDED_FOR_REGISTRATION)) {
                $regToken_str = $parser->get('token');
                $regToken = $this->entity_manager->getRepository(RegistrationToken::class)->findOneBy(['token' => $regToken_str]);
                if($regToken === null) {
                    return AjaxResponse::error(UserFactory::ErrorInvalidToken);
                }

                if ($regToken->getUser() !== null) {
                    return AjaxResponse::error(UserFactory::ErrorInvalidToken);
                }
            }

            if ($entityManager->getRepository(RegistrationLog::class)->countRecentRegistrations($request->getClientIp()) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_REG, 2))
                return AjaxResponse::error(UserFactory::ErrorTooManyRegistrations);

            $referred_player = null;
            if ($parser->has('refer', true)) {

                $refer_name = $parser->get('refer', null);
                $refer = $refer_name ? $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['name' => $refer_name, 'active' => true]) : null;

                if ($refer) $referred_player = $refer->getUser();
                else {

                    $potential_player = $this->entity_manager->getRepository(User::class)->findOneByNameOrDisplayName($refer_name);
                    if ($potential_player)
                        return AjaxResponse::error('invalid_fields', ['fields' => [$translator->trans('Der eingegebene Spieler ist nicht für das Patenschafts-Programm registriert. Bitte ihn, die "Freundschaft"-Seite in seiner Seele aufzurufen; er wird dann automatisch registriert. Erst dann kannst du ihn als Sponsor auswählen.', [], 'login')]]);
                    else return AjaxResponse::error('invalid_fields', ['fields' => [$translator->trans('Der eingegebene Pate ist ungültig. Um dich ohne einen Paten anzumelden, lasse das Feld frei.', [], 'login')]]);
                }
            }

            $user = $factory->createUser(
                $parser->trimmed('user'),
                $parser->trimmed('mail1'),
                $parser->trimmed('pass1'),
                $this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOKEN_NEEDED_FOR_REGISTRATION) && $regToken,
                $error
            );

            $user?->setTosver(1);

            switch ($error) {
                case UserFactory::ErrorNone:
                    try {
                        $user->setLanguage($this->getUserLanguage());
                        $entityManager->persist( (new RegistrationLog())
                            ->setUser($user)
                            ->setDate(new \DateTime())
                            ->setIdentifier( md5($request->getClientIp()) )
                        );

                        if ($campaign = $session->get('campaign')) {
                            $campaign_obj = $entityManager->getRepository(MarketingCampaign::class)->find($campaign);
                            if ($campaign_obj)
                                $entityManager->persist( (new MarketingCampaignConversion())
                                    ->setUser($user)
                                    ->setTime(new DateTime())
                                    ->setCampaign($campaign_obj)
                                );
                        }

                        if ($referred_player)
                            $entityManager->persist( (new UserSponsorship())
                                ->setSponsor( $referred_player )
                                ->setUser( $user )
                                ->setCountedHeroExp(0)->setCountedSoulPoints(0)
                                ->setTimestamp(new DateTime())
                            );

                        if ($this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOKEN_NEEDED_FOR_REGISTRATION) && $regToken) {
                            $user->setRegistrationToken($regToken);
                            $regToken->setUser($user);
                            $this->entity_manager->persist($regToken);
                        }

                        $entityManager->persist( $user );
                        $entityManager->flush();
                    } catch (Exception $e) {
                        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
                    }
                    $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                    $this->container->get('security.token_storage')->setToken($token);

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
     * @param string $code
     * @param TranslatorInterface $translator
     * @param EternalTwinHandler $etwin
     * @param SessionInterface $session
     * @param UserHandler $userHandler
     * @return Response
     */
    #[Route(path: 'jx/public/login/etwin/{code}', name: 'etwin_login')]
    public function login_via_etwin(string $code, TranslatorInterface $translator, EternalTwinHandler $etwin, SessionInterface $session, UserHandler $userHandler): Response {

        $myhordes_user = $this->getUser();
        if ($myhordes_user && $myhordes_user->getEternalID())
            throw new DynamicAjaxResetException(Request::createFromGlobals());

        if ($myhordes_user && !$userHandler->hasRole($myhordes_user, 'ROLE_NATURAL'))
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

            // Update user name
            if ($potential_user !== null && $myhordes_user === null) {
                $etu = substr($user->getDisplayName(),0,32);

                if (!$potential_user->getNoAutomaticNameManagement() && $etu !== $potential_user->getName() && $userHandler->isNameValid($etu)) {
                    $history = $potential_user->getNameHistory() ?? [];
                    if(!in_array($etu, $history))
                        $history[] = $etu;
                    $potential_user->setNameHistory(array_filter(array_unique($history)));
                    $this->entity_manager->persist( $potential_user->setDisplayName( $potential_user->getUsername() === $etu ? null : $etu )->setLastNameChange(new DateTime()) );

                    try {
                        $this->entity_manager->flush();
                        $this->addFlash('notice', $translator->trans('Du hast deinen Anzeigenamen auf EternalTwin geändert. Wir haben diese Änderung soeben für dich übernommen!', [], 'login'));
                    } catch (Exception $e) {}
                }
            }

            return $this->render( 'ajax/public/et_welcome.html.twig', [
                'refer' => $session->get('refer'),
                'etwin_user' => $user,
                'etwin_user_valid' => $userHandler->isNameValid( $user->getDisplayName(), custom_length: 32 ),
                'etwin_mail' => $user->getEmailAddress(),
                'target_user' => $potential_user,
                'current_user' => $myhordes_user
            ] );


        } else $this->addFlash('error', $translator->trans('Fehler bei der Datenübertragung.', [], 'login'));

        die;
    }

    /**
     * @param Request $request
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @param UserFactory $userFactory
     * @param UserPasswordHasherInterface $pass
     * @param TranslatorInterface $trans
     * @param ConfMaster $conf
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route(path: 'api/public/etwin/confirm', name: 'api_etwin_confirm')]
    public function etwin_confirm_api(Request $request, JSONRequestParser $parser, SessionInterface $session, UserFactory $userFactory,
                                      UserPasswordHasherInterface $pass, TranslatorInterface $trans, ConfMaster $conf, TranslatorInterface $translator, UserHandler $userHandler): Response {

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

            if ($myhordes_user)
                return new RedirectResponse($this->generateUrl( 'api_login' ));
            else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        // Case B - Account Creation
        elseif ($myhordes_user === null && $session->has('_etwin_user') && !$session->get('_etwin_login', false)) {
            if ($this->entity_manager->getRepository(RegistrationLog::class)->countRecentRegistrations($request->getClientIp()) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_REG, 2))
                return AjaxResponse::error(UserFactory::ErrorTooManyRegistrations);

            if ($this->entity_manager->getRepository(AntiSpamDomains::class)->findOneBy( ['type' => DomainBlacklistType::EternalTwinID, 'domain' => DomainBlacklistType::EternalTwinID->convert( $etwin_user->getID() )] ))
                return AjaxResponse::error(UserFactory::ErrorUserExists);

            if (!$parser->valid()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            if (!$parser->has_all( ['mail1','mail2'], true ))
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $violations = Validation::createValidator()->validate( $parser->all( true ), new Constraints\Collection([
                'fields' => [
                    'mail1' => [
                        new Constraints\Email( message: $translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login')),
                        new Constraints\Callback( [ 'callback' => function(string $mail, ExecutionContextInterface $context) use ($translator, $userHandler) {
							if (!$userHandler->isEmailValid($mail)) {
								$context->buildViolation($translator->trans('Die eingegebene E-Mail Adresse ist nicht gültig.', [], 'login'))
									->atPath('mail1')
									->addViolation();
							}
                        } ] )
                    ],
                    'mail2' => new Constraints\EqualTo(
                        ['value' => $parser->trimmed( 'mail1'), 'message' => $translator->trans('Die eingegebenen E-Mail Adressen stimmen nicht überein.', [], 'login')]),
                    'tos' => new Constraints\IsTrue(message: $translator->trans('Bitte stimme der Datenschutzerklärung und den Nutzungsbedingungen zu.', [], 'login'))
                ],
                'allowExtraFields' => true,
            ]) );

            if ($violations->count() > 0) {
                $v = [];
                foreach ($violations as &$violation)
                    /** @var ConstraintViolationInterface $violation */
                    $v[] = $violation->getMessage();

                return AjaxResponse::error( 'invalid_fields', ['fields' => $v] );
            }

            $referred_player = null;
            if ($parser->has('refer', true)) {

                $refer_name = $parser->get('refer', null);
                $refer = $refer_name ? $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['name' => $refer_name, 'active' => true]) : null;

                if ($refer) $referred_player = $refer->getUser();
                else {

                    $potential_player = $this->entity_manager->getRepository(User::class)->findOneByNameOrDisplayName($refer_name);
                    if ($potential_player)
                        return AjaxResponse::error('invalid_fields', ['fields' => [$translator->trans('Der eingegebene Spieler ist nicht für das Patenschafts-Programm registriert. Bitte ihn, die "Freundschaft"-Seite in seiner Seele aufzurufen; er wird dann automatisch registriert. Erst dann kannst du ihn als Sponsor auswählen.', [], 'login')]]);
                    else return AjaxResponse::error('invalid_fields', ['fields' => [$translator->trans('Der eingegebene Pate ist ungültig. Um dich ohne einen Paten anzumelden, lasse das Feld frei.', [], 'login')]]);
                }
            }

            $override_name = null;
            if ($parser->has('name', true)) {

                $override_name = $parser->get('name', null);
                if (!$userHandler->isNameValid($override_name, custom_length: 32)) {
                    return AjaxResponse::error('invalid_fields', ['fields' => [$translator->trans('Der eingegebene Spielername verstößt gegen die Richtlinien von MyHordes und kann nicht verwendet werden.', [], 'login')]]);
                }
            }

            $new_user = $userFactory->importUser( $etwin_user, $parser->get('mail1'), false, $error, $override_name );

            switch ($error) {
                case UserFactory::ErrorNone:
                    try {
                        $new_user->setTosver(1);
                        $this->entity_manager->persist( $new_user );
                        $new_user->setLanguage($this->getUserLanguage());
                        $this->entity_manager->persist( (new RegistrationLog())
                                                            ->setUser($new_user)
                                                            ->setDate(new \DateTime())
                                                            ->setIdentifier( md5($request->getClientIp()) )
                        );

                        if ($override_name !== null) $new_user->setNoAutomaticNameManagement(true);

                        if ($campaign = $session->get('campaign')) {
                            $campaign_obj = $this->entity_manager->getRepository(MarketingCampaign::class)->find($campaign);
                            if ($campaign_obj)
                                $this->entity_manager->persist( (new MarketingCampaignConversion())
                                                             ->setUser($new_user)
                                                             ->setTime(new DateTime())
                                                             ->setCampaign($campaign_obj)
                                );
                        }

                        if ($referred_player)
                            $this->entity_manager->persist( (new UserSponsorship())
                                                                ->setSponsor( $referred_player )
                                                                ->setUser( $new_user )
                                                                ->setCountedHeroExp(0)->setCountedSoulPoints(0)
                                                                ->setTimestamp(new DateTime())
                            );


                        $this->entity_manager->flush();
                    } catch (Exception $e) {
                        return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                    }

                    $session->set('_etwin_login', true);
                    return AjaxResponse::success( 'validation');

                case UserFactory::ErrorInvalidParams:
                    return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                default: return AjaxResponse::error($error);
            }
        }

        // Case C - Account linking
        elseif ($myhordes_user !== null && $session->has('_etwin_user') && $session->get('_etwin_local') === $myhordes_user->getId()) {

            if ($myhordes_user->getEternalID() || !$userHandler->hasRole($myhordes_user, 'ROLE_NATURAL')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            if (empty($password) || !$pass->isPasswordValid( $myhordes_user, $password ))
                return AjaxResponse::error( SoulController::ErrorUserEditPasswordIncorrect );

            if ($this->entity_manager->getRepository(User::class)->findOneByEternalID( $etwin_user->getID() ))
                return AjaxResponse::error( SoulController::ErrorETwinImportProfileInUse );

            $myhordes_user->setEternalID( $etwin_user->getID() );
            $new_display_name = preg_replace('/[^\w]/', '', trim($etwin_user->getDisplayName()));
            if (!$myhordes_user->getNoAutomaticNameManagement() && $new_display_name !== $myhordes_user->getName() && $userHandler->isNameValid( $new_display_name )) {
                $history = $myhordes_user->getNameHistory() ?? [];
                if(!in_array($myhordes_user->getName(), $history))
                    $history[] = $myhordes_user->getName();
                $myhordes_user->setNameHistory(array_filter(array_unique($history)));
                $myhordes_user->setDisplayName( $new_display_name );
            }

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
     * @param SessionInterface $session
     * @return Response
     */
    #[Route(path: 'api/public/etwin/cancel', name: 'api_etwin_cancel')]
    public function etwin_cancel_api(SessionInterface $session): Response {
        $session->remove('_etwin_user');
        $session->remove('_etwin_login');
        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/public/login', name: 'api_login')]
    public function login_api(TranslatorInterface $trans, JSONRequestParser $parser): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return new AjaxResponse( ['success' => false ] );

        $flush = false;

        // If there is an open password reset validation, a successful login closes it
        $reset_validation = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user, UserPendingValidation::ResetValidation);
        if ($reset_validation) {
            $this->entity_manager->remove($reset_validation);
            $user->setPendingValidation(null);
            $this->entity_manager->persist($user);
            $flush = true;
        }

        if ($user->getDeleteAfter() !== null) {
            $user->setDeleteAfter(null);
            $this->entity_manager->persist($user);
            $this->addFlash('notice', $trans->trans('Willkommen zurück! Dein Account ist nicht länger zur Löschung vorgemerkt.', [], 'login'));
            $flush = true;
        }

        if ($user->getLanguage() === null) {
            $user->setLanguage($this->getUserLanguage());
            $this->entity_manager->persist($user);
            $flush = true;
        }

        if ($flush) try {
            $this->entity_manager->flush();
        } catch(Exception $e) {}

        // return new Response('', 200, ['X-AJAX-Control' => 'reload']);

        if (!$user->getValidated())
            return (new AjaxResponse( ['success' => true, 'require_validation' => true ] ))->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
        else return (new AjaxResponse( ['success' => true, 'require_validation' => false ] ))->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
    }

    /**
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserFactory $factory
     * @return Response
     */
    #[Route(path: 'api/public/validate', name: 'api_validate')]
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
                    'exactMessage' => $translator->trans('Der Validierungscode muss { limit } Zeichen umfassen.', [], 'login'),
                ])
        ]) );

        if ($violations->count() === 0) {

            /** @var User $user */
            $user = $this->getUser();

            if ($factory->validateUser( $user, $parser->trimmed('validate'), $error )) {

                if ($this->getUser()) {
                    $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                    $this->container->get('security.token_storage')->setToken($token);
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
     * @return Response
     */
    #[Route(path: 'jx/public/welcome', name: 'public_welcome')]
    public function welcome(HTMLService $html): Response
    {
        $lang = $this->getUserLanguage();

        $lastNews = $this->entity_manager->getRepository(Announcement::class)->findLatestByLang( $lang );
        if ($lastNews) $lastNews->setText( $html->prepareEmotes( $lastNews->getText(), $lastNews->getSender() ) );

        return $this->render('ajax/public/intro.html.twig', $this->addDefaultTwigArgs(null, [
            'lang' => $lang,
            'lastChangelog' => $this->entity_manager->getRepository(Changelog::class)->findLatestByLang( $lang ),
            'lastNews' => $lastNews,
            'season' => $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true])
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/public/about', name: 'public_about')]
    public function about(): Response
    {
        return $this->render('ajax/public/about.html.twig', $this->addDefaultTwigArgs());
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/public/changelog/{id}', name: 'public_changelog', requirements: ['id' => '\d+'])]
    public function changelog(HTMLService $html, int $id = -1): Response
    {
        $lang = $this->getUserLanguage();

        $changelog = $this->entity_manager->getRepository(Changelog::class)->find( $id );
        if (!$changelog || $changelog->getLang() !== $lang) {
            $changelog = $this->entity_manager->getRepository(Changelog::class)->findLatestByLang($lang);
            return $changelog
                ? $this->redirectToRoute('public_changelog', ['id' => $changelog->getId()])
                : $this->redirectToRoute( 'public_welcome' );
        }

        $latest = $this->entity_manager->getRepository(Changelog::class)->findLatestByLang($lang);
        if ($latest) $latest->setText( $html->prepareEmotes( $latest->getText() ) );

        return $this->render('ajax/public/changelogs.html.twig', $this->addDefaultTwigArgs(null, [
            'latest' => $latest,
            'current' => $changelog,
            'all' => $this->entity_manager->getRepository(Changelog::class)->findByLang( $lang )
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/public/news', name: 'public_news')]
    public function news(HTMLService $html): Response
    {
        $lang = $this->getUserLanguage();
        return $this->render('ajax/public/news.html.twig', $this->addDefaultTwigArgs(null, [
            'all' => array_map( function(Announcement $a) use (&$html) {
                return $a->setText( $html->prepareEmotes( $a->getText(), $a->getSender() ) );
            }, $this->entity_manager->getRepository(Announcement::class)->findByLang($lang, [], 5))
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/public/privacy', name: 'public_privacy')]
    public function privacy(): Response
    {
        return $this->render('ajax/public/privacy.html.twig', $this->addDefaultTwigArgs());
    }
}
