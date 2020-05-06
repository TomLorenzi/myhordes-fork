<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validation;

class UserFactory
{
    private $entity_manager;
    private $encoder;
    private $locksmith;
    private $url;

    const ErrorNone = 0;
    const ErrorUserExists        = ErrorHelper::BaseUserErrors + 1;
    const ErrorMailExists        = ErrorHelper::BaseUserErrors + 2;
    const ErrorInvalidParams     = ErrorHelper::BaseUserErrors + 3;
    const ErrorDatabaseException = ErrorHelper::BaseUserErrors + 4;
    const ErrorValidationExists  = ErrorHelper::BaseUserErrors + 5;

    public function __construct( EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, Locksmith $l, UrlGeneratorInterface $url)
    {
        $this->entity_manager = $em;
        $this->encoder = $passwordEncoder;
        $this->locksmith = $l;
        $this->url = $url;
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

        if ($pending->getUser() === null || !$user->isEqualTo( $pending->getUser() )) {
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

        if ($this->entity_manager
            ->getRepository(UserPendingValidation::class)
            ->findOneByUserAndType($user, UserPendingValidation::ResetValidation)) {
            $error = self::ErrorValidationExists;
            return null;
        }

        $new_validation = new UserPendingValidation();
        $key = $new_validation->setTime(new DateTime())->setType(UserPendingValidation::ResetValidation)->generatePKey( );
        $user->setPendingValidation( $new_validation );

        $url = $this->url->generate('public_reset', ['pkey' => $key]);

        mail(
            $email,
            'MyHordes - Password reset',
            "Please visit this link to reset your password: <a href='$url'>$url</a>!",
            [
                'MIME-Version' => '1.0',
                'Content-type' => 'text/html; charset=UTF-8',
                'From' => 'The Undead Mailman <mailzombie@' . $_SERVER['SERVER_NAME'] . '>'
            ]
        );

        return $user;
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

        if (!$validated) {
            $new_validation = new UserPendingValidation();
            $key = $new_validation->setTime(new DateTime())->generatePKey( );
            $new_user->setPendingValidation( $new_validation );

            mail(
                $email,
                'MyHordes - Account Validation',
                "Your validation code is <b>$key</b>. Thank you for playing MyHordes!",
                [
                    'MIME-Version' => '1.0',
                    'Content-type' => 'text/html; charset=UTF-8',
                    'From' => 'The Undead Mailman <mailzombie@' . $_SERVER['SERVER_NAME'] . '>'
                ]
            );
        }

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
            $this->entity_manager->persist( $user );
            $this->entity_manager->remove( $pending );
            $this->entity_manager->flush();
            return true;
        } catch (Exception $e) {
            $error = self::ErrorDatabaseException;
            return false;
        }
    }
}