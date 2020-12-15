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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserFactory
{
    private $entity_manager;
    private $encoder;
    private $locksmith;
    private $url;
    private $twig;
    private $trans;
    private $perm;

    const ErrorNone = 0;
    const ErrorUserExists        = ErrorHelper::BaseUserErrors + 1;
    const ErrorMailExists        = ErrorHelper::BaseUserErrors + 2;
    const ErrorInvalidParams     = ErrorHelper::BaseUserErrors + 3;
    const ErrorDatabaseException = ErrorHelper::BaseUserErrors + 4;
    const ErrorValidationExists  = ErrorHelper::BaseUserErrors + 5;
    const ErrorTooManyRegistrations = ErrorHelper::BaseUserErrors + 6;

    public function __construct( EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder,
                                 Locksmith $l, UrlGeneratorInterface $url, Environment $e, TranslatorInterface $t,
                                 PermissionHandler $p)
    {
        $this->entity_manager = $em;
        $this->encoder = $passwordEncoder;
        $this->locksmith = $l;
        $this->url = $url;
        $this->twig = $e;
        $this->trans = $t;
        $this->perm = $p;
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
            $user->setPassword( $this->encoder->encodePassword($user, $password) );
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
        if ($user->getId() !== null)
            $validation = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user,$validationType);
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
        $new_user->setName( $name )->setEmail( $email )->setPassword( $this->encoder->encodePassword($new_user, $password) )->setValidated( $validated )->setSoulPoints(0);

        if ($validator->validate($new_user)->count() > 0) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        if (!$validated)
            $this->announceValidationToken( $this->ensureValidation( $new_user, UserPendingValidation::EMailValidation ) );
        else $this->entity_manager->persist($this->perm->associate($new_user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup )));

        return $new_user;
    }

    public function importUser( \EternalTwinClient\Object\User $etwin_user ): User {
        $i = 0;
        $new_name = $etwin_user->getDisplayName();

        if ($this->entity_manager->getRepository(User::class)->findOneByName($new_name))
            do {
                $it = "" . (++$i);
                $new_name = substr( $etwin_user->getUsername(), 0, 16 - mb_strlen( $it ) ) . $it;
            } while (!$this->entity_manager->getRepository(User::class)->findOneByName($new_name));

        $new_user = (new User())
            ->setName( $new_name )
            ->setEmail( "{$etwin_user->getID()}@user.eternal-twin.net" )
            ->setPassword( null )
            ->setValidated( true )
            ->setEternalID( $etwin_user->getID() )
            ->setSoulPoints(0);

        if ($new_name !== $etwin_user->getUsername())
            $new_user->setDisplayName( $etwin_user->getUsername() );

        $this->entity_manager->persist($this->perm->associate($new_user, $this->perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup )));
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
        return mail(
            $token->getUser()->getEmail(),
            "MyHordes - {$headline}", $message,
            [
                'MIME-Version' => '1.0',
                'Content-type' => 'text/html; charset=UTF-8',
                'From' => 'The Undead Mailman <mailzombie@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>'
            ]
        );
    }
}