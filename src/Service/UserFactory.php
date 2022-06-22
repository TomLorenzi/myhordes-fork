<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\Inventory;
use App\Entity\Picto;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserPendingValidation;
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

    const ErrorNone = 0;
    const ErrorUserExists        = ErrorHelper::BaseUserErrors + 1;
    const ErrorMailExists        = ErrorHelper::BaseUserErrors + 2;
    const ErrorInvalidParams     = ErrorHelper::BaseUserErrors + 3;
    const ErrorDatabaseException = ErrorHelper::BaseUserErrors + 4;
    const ErrorValidationExists  = ErrorHelper::BaseUserErrors + 5;
    const ErrorTooManyRegistrations = ErrorHelper::BaseUserErrors + 6;
    const ErrorTooManyMails      = ErrorHelper::BaseUserErrors + 7;

    public function __construct( EntityManagerInterface $em, UserPasswordHasherInterface $passwordEncoder,
                                 Locksmith $l, UrlGeneratorInterface $url, Environment $e, TranslatorInterface $t,
                                 PermissionHandler $p, MailerInterface $mailer)
    {
        $this->entity_manager = $em;
        $this->encoder = $passwordEncoder;
        $this->locksmith = $l;
        $this->url = $url;
        $this->twig = $e;
        $this->trans = $t;
        $this->perm = $p;
        $this->mailer = $mailer;
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

        if (!$validated)
            $this->announceValidationToken( $this->ensureValidation( $new_user, UserPendingValidation::EMailValidation ) );
        else $this->entity_manager->persist($this->perm->associate($new_user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup )));

        return $new_user;
    }

    public function importUser( \EternalTwinClient\Object\User $etwin_user, ?string $mail, $validated, ?int &$error ): ?User {
        $error = 0;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        $i = 0;
        $user_mail = $mail ?? "{$etwin_user->getID()}@user.eternal-twin.net";
        $display_name = substr(preg_replace('/[^\w]/', '', trim($etwin_user->getDisplayName())),0,32);
        $new_name = substr($display_name,0,16);

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

    public function announceValidationToken(UserPendingValidation $token): bool {

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

        try {
            $this->mailer->send( (new Email())
                ->from( 'The Undead Mailman <mailzombie@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>' )
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