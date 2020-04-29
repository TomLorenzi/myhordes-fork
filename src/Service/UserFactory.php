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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validation;

class UserFactory
{
    private $entity_manager;
    private $encoder;
    private $locksmith;

    const ErrorNone = 0;
    const ErrorUserExists        = ErrorHelper::BaseUserErrors + 1;
    const ErrorMailExists        = ErrorHelper::BaseUserErrors + 2;
    const ErrorInvalidParams     = ErrorHelper::BaseUserErrors + 3;
    const ErrorDatabaseException = ErrorHelper::BaseUserErrors + 4;

    public function __construct( EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, Locksmith $l)
    {
        $this->entity_manager = $em;
        $this->encoder = $passwordEncoder;
        $this->locksmith = $l;
    }

    public function resetUserPassword( string $email, string $password ): ?User {
        $error = 0;

        $lock = $this->locksmith->waitForLock( 'user-creation' );

        if (!($user = $this->entity_manager->getRepository(User::class)->findOneByMail( $email ))) {
            $error = self::ErrorInvalidParams;
            return null;
        }

        $user->setPassword( $this->encoder->encodePassword($user, $password) );
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
            $source = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $key = "";
            for ($i = 0; $i < 16; $i++) $key .= $source[ mt_rand(0, strlen($source) - 1) ];

            $new_validation = new UserPendingValidation();
            $new_validation->setPkey( $key );
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

        if (($pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByTokenAndUser(
                $validation_key, $user
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