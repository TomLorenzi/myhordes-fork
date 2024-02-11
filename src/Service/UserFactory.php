<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Inventory;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserPendingValidation;
use App\Enum\ServerSetting;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserFactory
{
    private EntityManagerInterface $entity_manager;
    private UserPasswordHasherInterface $encoder;
    private Locksmith $locksmith;
    private UrlGeneratorInterface $url;
    private Environment $twig;
    private TranslatorInterface $trans;
    private PermissionHandler $perm;
    private MailerInterface $mailer;
    private ConfMaster $conf;

    private UserHandler $userHandler;

    const ErrorNone = 0;
    const ErrorUserExists           = ErrorHelper::BaseUserErrors + 1;
    const ErrorMailExists           = ErrorHelper::BaseUserErrors + 2;
    const ErrorInvalidParams        = ErrorHelper::BaseUserErrors + 3;
    const ErrorDatabaseException    = ErrorHelper::BaseUserErrors + 4;
    const ErrorValidationExists     = ErrorHelper::BaseUserErrors + 5;
    const ErrorTooManyRegistrations = ErrorHelper::BaseUserErrors + 6;
    const ErrorTooManyMails         = ErrorHelper::BaseUserErrors + 7;
    const ErrorInvalidToken         = ErrorHelper::BaseUserErrors + 8;

    public function __construct( EntityManagerInterface $em, UserPasswordHasherInterface $passwordEncoder,
                                 Locksmith $l, UrlGeneratorInterface $url, Environment $e, TranslatorInterface $t,
                                 PermissionHandler $p, MailerInterface $mailer, ConfMaster $conf, UserHandler $userHandler)
    {
        $this->entity_manager = $em;
        $this->encoder = $passwordEncoder;
        $this->locksmith = $l;
        $this->url = $url;
        $this->twig = $e;
        $this->trans = $t;
        $this->perm = $p;
        $this->mailer = $mailer;
        $this->conf = $conf;
        $this->userHandler = $userHandler;
    }

    public function resetUserPassword( string $email, string $validation_key, string $password, ?int &$error ): ?User {
        $error = self::ErrorNone;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        /** @var $user User */
        if (!($user = $this->entity_manager->getRepository(User::class)->findOneByMail( $email ))) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        if (($pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByTokenAndUserandType(
                $validation_key, $user, UserPendingValidation::ResetValidation
            )) === null) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        if ($pending->getUser() === null || $user->getId() !== $pending->getUser()->getId()) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        try {
            $user->setPassword( $this->encoder->hashPassword($user, $password) );
            $user->setPendingValidation(null);
            $this->entity_manager->persist( $user );
            $this->entity_manager->remove( $pending );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            $error = self::ErrorDatabaseException;
        }

        return $user;
    }

    public function prepareUserPasswordReset( string $email, ?int &$error ): ?User {
        $error = self::ErrorNone;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        if (!($user = $this->entity_manager->getRepository(User::class)->findOneByMail( $email ))) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        /** @var UserPendingValidation $existing_val */
        if (($existing_val = $this->entity_manager
            ->getRepository(UserPendingValidation::class)
            ->findOneByUserAndType($user, UserPendingValidation::ResetValidation)) &&
            (time() - $existing_val->getTime()->getTimestamp() < 3600)
        ) {
            $error = self::ErrorValidationExists;
            return null;
        }

        if (!$this->announceValidationToken( $this->ensureValidation( $user, UserPendingValidation::ResetValidation ) )) return null;
        return $user;
    }

    public function ensureValidation(User $user, int $validationType, bool $regenerate = false): UserPendingValidation {
        /** @var UserPendingValidation $validation */
        $validation = null;
        if ($user->getId() !== null) {
            $validation = $user->getPendingValidation();
            if ($validation && $validation->getType() !== $validationType) {
                if ($validation->getType() === UserPendingValidation::ChangeEmailValidation)
                    $user->setPendingEmail(null);
                $validation->setTime(new DateTime())->setType($validationType)->generatePKey();
            }
        }
        if ($validation === null) {
            $validation = new UserPendingValidation();
            $validation->setTime(new DateTime())->setType($validationType)->generatePKey( );
            $user->setPendingValidation( $validation );
        } elseif ($regenerate) $validation->generatePKey();
        return $validation;
    }

    public function createUser( string $name, string $email, string $password, bool $validated, ?int &$error ): ?User {
        $error = 0;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        if ($this->entity_manager->getRepository(User::class)->findOneByName( $name )) {
            $error = self::ErrorUserExists;
            return null;
        }
        if ($this->entity_manager->getRepository(User::class)->findOneByMail( $email )) {
            $error = self::ErrorMailExists;
            return null;
        }

        $validator = Validation::createValidator();

        $new_user = new User();
        $new_user->setName( $name )->setEmail( $email )->setPassword( $this->encoder->hashPassword($new_user, $password) )->setValidated( $validated )->setSoulPoints(0);

        if ($validator->validate($new_user)->count() > 0) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        $this->postProcessNewUser( $new_user );

        if (!$validated)
            $this->announceValidationToken( $this->ensureValidation( $new_user, UserPendingValidation::EMailValidation ) );
        else $this->entity_manager->persist($this->perm->associate($new_user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup )));

        return $new_user;
    }

    public function postProcessNewUser( User $user): void {
        $conf = $this->conf->getGlobalConf();

        if ($conf->get( MyHordesConf::CONF_STAGING_ENABLED, false )) {

            if ( $conf->get( MyHordesConf::CONF_STAGING_TOWN_ENABLED, false )) {

                $days = $conf->get( MyHordesConf::CONF_STAGING_TOWN_DAYS, 0 );
                $score = $days * ($days + 1) / 2;

                $id_offset = 100000000;
                do {
                    $id_identifier = mt_rand(1,$id_offset-1);
                } while ($this->entity_manager->getRepository(TownRankingProxy::class)->count(['baseID' => $id_offset + $id_identifier]));

                $user->setSoulPoints( $user->getSoulPoints() + $score );

                $this->entity_manager->persist($town = (new TownRankingProxy())
                    ->setName( 'Ghost Town' )
                    ->setBaseID(  $id_offset + $id_identifier )
                    ->setImported( false )
                    ->setLanguage( 'multi' )
                    ->setType( $this->entity_manager->getRepository(TownClass::class)->findOneBy(['name' => TownClass::DEFAULT]) )
                    ->setSeason( $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]) )
                    ->setDays( $days )
                    ->setPopulation( 1 )
                    ->setV1( false )
                    ->addDisableFlag( TownRankingProxy::DISABLE_RANKING )
                    ->addCitizen(
                        (new CitizenRankingProxy())
                            ->setBaseID( $id_offset + $id_identifier )
                            ->setImportID( 0 )
                            ->setUser( $user )
                            ->setCod( $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => CauseOfDeath::Apocalypse] ) )
                            ->setComment( 'Can\'t remember playing this one...' )
                            ->setLastWords( null )
                            ->setDay( $days )
                            ->setConfirmed( true )
                            ->setPoints( $days * ($days + 1) / 2 )
                            ->setLimitedImport( false )
                            ->setCleanupUsername(null)
                            ->setCleanupType('')
                    )
                );

                $this->entity_manager->persist(
                    (new Picto())
                        ->setPrototype($this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_ptame_#00'))
                        ->setPersisted(2)
                        ->setUser($user)
                        ->setCount( $score )
                        ->setTownEntry( $town )
                );
            }

            $user->setHeroDaysSpent( $user->getHeroDaysSpent() + $conf->get( MyHordesConf::CONF_STAGING_HERODAYS, 0 ) );

            foreach ( $conf->get( MyHordesConf::CONF_STAGING_FEATURES, [] ) as $feature )
                $this->entity_manager->persist(
                    (new FeatureUnlock())
                        ->setPrototype( $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => $feature]) )
                        ->setUser( $user )
                        ->setExpirationMode( FeatureUnlock::FeatureExpirationTownCount )
                        ->setTownCount( 5 )
                );
        }
    }

    public function importUser( \EternalTwinClient\Object\User $etwin_user, ?string $mail, $validated, ?int &$error, ?string $override_name = null ): ?User {
        $error = 0;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        $i = 0;
        $user_mail = $mail ?? "{$etwin_user->getID()}@user.eternal-twin.net";

        $etwin_name = $override_name ?? preg_replace('/[^\w]/', '', trim($etwin_user->getDisplayName()));
        if ($override_name !== null || $this->userHandler->isNameValid( $etwin_name ))
            $display_name = substr($etwin_name,0,32);
        else
            $display_name = $override_name ?? ('u' . time());

        $new_name = substr($display_name,0,16);
        $count = 1;
        while ($this->entity_manager->getRepository(User::class)->findOneByName( $new_name ) && $count < 999)
            $new_name = substr($display_name,0,13) . str_pad( "" . ($count++), 3, "0", STR_PAD_LEFT );

        if ($this->entity_manager->getRepository(User::class)->findOneByMail( $user_mail )) {
            $error = self::ErrorMailExists;
            return null;
        }

        while ($this->entity_manager->getRepository(User::class)->findOneByName($new_name)) {
            $it = "" . (++$i);
            $new_name = substr( $display_name, 0, 16 - strlen( $it ) ) . $it;
        }

        $new_user = (new User())
            ->setName( $new_name )
            ->setEmail( $user_mail )
            ->setPassword( null )
            ->setValidated( $validated )
            ->setEternalID( $etwin_user->getID() )
            ->setSoulPoints(0);

        if ($new_name !== $display_name)
            $new_user->setDisplayName( $display_name );

        $validator = Validation::createValidator();
        if ($validator->validate($new_user)->count() > 0) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        $this->postProcessNewUser( $new_user );

        if (!$validated)
            $this->announceValidationToken( $this->ensureValidation( $new_user, UserPendingValidation::EMailValidation ) );
        else $this->entity_manager->persist($this->perm->associate($new_user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup )));

        return $new_user;
    }

    public function validateUser( ?User $user, string $validation_key, ?int &$error ): bool {
        $error = self::ErrorNone;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        if (($pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByTokenAndUserandType(
                $validation_key, $user, UserPendingValidation::EMailValidation
            )) === null) {
            $error = self::ErrorInvalidParams;
            return false;
        }

        if ($pending->getUser() === null || ($user !== null && !$user->isEqualTo( $pending->getUser() ))) {
            $error = self::ErrorInvalidParams;
            return false;
        }

        if ($user === null) $user = $pending->getUser();

        try {
            $user->setValidated( true );
            $this->entity_manager->persist($this->perm->associate( $user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup ) ) );
            $this->entity_manager->persist( $user );
            $this->entity_manager->remove( $pending );
            $this->entity_manager->flush();
            return true;
        } catch (Exception $e) {
            $error = self::ErrorDatabaseException;
            return false;
        }
    }

    public function announceValidationToken(UserPendingValidation $token, bool $force = false): bool {

        if (!$token->getUser() || !$token->getPkey()) return false;

        $headline = null;
        $message = null;
        switch ($token->getType()) {
            case UserPendingValidation::EMailValidation:
                $headline = $this->trans->trans('Account validieren', [], 'mail');
                $message = $this->twig->render( 'mail/validation.html.twig', [
                    'title' => $headline,
                    'user' => $token->getUser(),
                    'token' => $token
                ] );
                break;
            case UserPendingValidation::ChangeEmailValidation:
                $headline = $this->trans->trans('E-Mail Validierung', [], 'mail');
                $message = $this->twig->render( 'mail/email_change_validation.html.twig', [
                    'title' => $headline,
                    'user' => $token->getUser(),
                    'token' => $token
                ] );
                break;
            case UserPendingValidation::ResetValidation:
                $headline = $this->trans->trans('Passwort zurücksetzen', [], 'mail');
                $message = $this->twig->render( 'mail/passreset.html.twig', [
                    'title' => $headline,
                    'user' => $token->getUser(),
                    'token' => $token,
                    'url' => $this->url->generate('public_reset', ['pkey' => $token->getPkey()], UrlGeneratorInterface::ABSOLUTE_URL)
                ] );
                break;
            default: break;
        }

        if ($message === null || $headline === null) return false;

        $from_domain = ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $domain_slice = $this->conf->getGlobalConf()->get( MyHordesConf::CONF_MAIL_DOMAINCAP, 0 );
        if ($domain_slice >= 2)
            $from_domain = implode('.', array_slice( explode( '.', $from_domain ), -$domain_slice ));

        try {
            if ($force || !$this->conf->serverSetting( ServerSetting::DisableAutomaticUserValidationMails ) || $token->getType() !== UserPendingValidation::EMailValidation)
                $this->mailer->send( (new Email())
                    ->from( "The Undead Mailman <mailzombie@{$from_domain}>" )
                    ->to( $token->getType() === UserPendingValidation::ChangeEmailValidation ? $token->getUser()->getPendingEmail() : $token->getUser()->getEmail() )
                    ->subject( "MyHordes - $headline" )
                    ->html( $message )
                );
            return true;
        } catch (\Throwable $t) {
            return false;
        }
    }
}