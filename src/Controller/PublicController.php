<?php

namespace App\Controller;

use App\Entity\AntiSpamDomains;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Exception\DynamicAjaxResetException;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
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
     * @return Response
     */
    public function login(): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));
        return $this->render( 'ajax/public/login.html.twig', $this->addDefaultTwigArgs() );
    }

    /**
     * @Route("jx/public/register", name="public_register")
     * @return Response
     */
    public function register(): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));
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
        JSONRequestParser $parser,
        TranslatorInterface $translator,
        UserFactory $factory,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
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
