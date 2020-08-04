<?php


namespace App\Command;


use App\Entity\Avatar;
use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\User;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserInfoCommand extends Command
{
    protected static $defaultName = 'app:users';

    private $entityManager;
    private $user_handler;
    private $pwenc;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserHandler $uh)
    {
        $this->entityManager = $em;
        $this->pwenc = $passwordEncoder;
        $this->user_handler = $uh;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Lists information about users.')
            ->setHelp('This command allows you list users, or get information about a specific user.')

            ->addArgument('UserID', InputArgument::OPTIONAL, 'The user ID')

            ->addOption('validation-pending', 'v0', InputOption::VALUE_NONE, 'Only list users with pending validation.')
            ->addOption('validated', 'v1', InputOption::VALUE_NONE, 'Only list validated users.')
            ->addOption('mods', 'm', InputOption::VALUE_NONE, 'Only list users with elevated permissions.')

            ->addOption('set-password', null, InputOption::VALUE_REQUIRED, 'Changes the user password; set to "auto" to auto-generate.', null)

            ->addOption('find-all-rps', null, InputOption::VALUE_REQUIRED, 'Gives all known RP to a user in the given lang')
            ->addOption('give-all-pictos', null, InputOption::VALUE_OPTIONAL, 'Gives all pictos once to a user')

            ->addOption('set-mod-level', null, InputOption::VALUE_REQUIRED, 'Sets the moderation level for a user (0 = normal user, 2 = oracle, 3 = mod, 4 = admin)')
            ->addOption('set-hero-days', null, InputOption::VALUE_REQUIRED, 'Set the amount of hero days spent to a user (and the associated skills)')

            ->addOption('set-avatar',   'a',   InputOption::VALUE_REQUIRED, 'Enter a local file name to use as an avatar for this user.')
            ->addOption('remove-avatar',null,  InputOption::VALUE_NONE,  'Removes a user avatar.')
            ->addOption('avatar-ext',   null,  InputOption::VALUE_OPTIONAL, 'Specify the file extension for --set-avatar. If omitted, the extension will be automatically determined.')
            ->addOption('avatar-small', null,  InputOption::VALUE_NONE,     'If used with --set-avatar, the given avatar will be used as small avatar if a normal avatar is already set. If used with --remove-avatar, only the small avatar will be deleted.')
            ->addOption('avatar-x',     null,  InputOption::VALUE_REQUIRED, 'Sets the image width. Should be set when Imagick is not available. Has no effect when uploading a small avatar.')
            ->addOption('avatar-y',     null,  InputOption::VALUE_REQUIRED, 'Sets the image height. Should be set when Imagick is not available. Has no effect when uploading a small avatar.')
            ->addOption('avatar-magick',null,  InputOption::VALUE_REQUIRED, 'When setting an avatar, "auto" will attempt to use Imagick (default), "force" will enforce Imagick and "raw" will disable Imagick.')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($userid = $input->getArgument('UserID')) {

            /** @var User $user */
            $user = null;
            if (is_numeric($userid))
                $user = $this->entityManager->getRepository(User::class)->find((int)$userid);

            if ($user === null && mb_strpos($userid, '@'))
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userid]);

            if ($user === null)
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['name' => $userid]);

            if ($user === null) throw new \Exception('User not found.');

            if (($modlv = $input->getOption('set-mod-level')) !== null) {
                $user->setRightsElevation($modlv);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($rpLang = $input->getOption('find-all-rps')) {
                $rps = $this->entityManager->getRepository(RolePlayText::class)->findAllByLang($rpLang);
                $count = 0;
                foreach ($rps as $rp) {
                    $alreadyfound = $this->entityManager->getRepository(FoundRolePlayText::class)->findByUserAndText($user, $rp);
                    if ($alreadyfound !== null)
                        continue;
                    $count++;
                    $foundrp = new FoundRolePlayText();
                    $foundrp->setUser($user)->setText($rp);
                    $user->getFoundTexts()->add($foundrp);

                    $this->entityManager->persist($foundrp);
                }
                echo "Added $count RPs to user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($count = $input->getOption('give-all-pictos')) {
                $pictoPrototypes = $this->entityManager->getRepository(PictoPrototype::class)->findAll();
                foreach ($pictoPrototypes as $pictoPrototype) {
                    $picto = $this->entityManager->getRepository(Picto::class)->findByUserAndTownAndPrototype($user, null, $pictoPrototype);
                    if($picto === null) $picto = new Picto();
                    $picto->setPrototype($pictoPrototype)
                        ->setPersisted(2)
                        ->setTown(null)
                        ->setTownEntry(null)
                        ->setUser($user)
                        ->setCount($picto->getCount()+1);

                    $this->entityManager->persist($picto);
                }
                echo "Added pictos to user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();

            } elseif ($newpw = $input->getOption('set-password')) {
                if ($newpw === 'auto') {
                    $newpw = '';
                    $source = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789-_$';
                    for ($i = 0; $i < 9; $i++) $newpw .= $source[ mt_rand(0, strlen($source) - 1) ];
                }

                $user->setPassword($this->pwenc->encodePassword( $user,$newpw ));
                $output->writeln("New password set: <info>$newpw</info>");
                $this->entityManager->persist($user);
                $this->entityManager->flush();

            } elseif ($heroDaysCount = $input->getOption('set-hero-days')) {
                $user->setHeroDaysSpent($heroDaysCount);
                $output->writeln("Hero Days updated.");
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($input->getOption('remove-avatar')) {

                $a = $user->getAvatar();
                if ($a !== null) {

                    if ($input->getOption('avatar-small')) {
                        $a->setSmallImage(null)->setSmallName($a->getFilename());
                        $this->entityManager->persist($a);
                        $output->writeln('Small avatar has been reset.');
                    } else {
                        $user->setAvatar(null);
                        $this->entityManager->remove($a);
                        $output->writeln('Avatar has been deleted.');
                    }

                    $this->entityManager->flush();

                } else $output->writeln('User does not have an avatar.');

            } elseif ($avatar = $input->getOption('set-avatar')) {

                if (!file_exists($avatar))
                    throw new \Exception('File not found.');

                $m = $input->getOption('avatar-magick');
                switch ($m) {
                    case "force": $m = UserHandler::ImageProcessingForceImagick; break;
                    case "raw":   $m = UserHandler::ImageProcessingDisableImagick; break;
                    default:      $m = UserHandler::ImageProcessingPreferImagick; break;
                }

                $ext = strtolower($input->getOption('avatar-ext') ?: pathinfo($avatar, PATHINFO_EXTENSION ));
                $error = $input->getOption('avatar-small')
                    ? $this->user_handler->setUserSmallAvatar($user, file_get_contents($avatar))
                    : $this->user_handler->setUserBaseAvatar($user, file_get_contents($avatar), $m, $ext, (int)$input->getOption('avatar-x'), (int)$input->getOption('avatar-y'));

                if ($error !== UserHandler::NoError) throw new \Exception("Error: $error");

                $output->writeln("Avatar updated.");
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        } else {
            /** @var User[] $users */
            $users = array_filter( $this->entityManager->getRepository(User::class)->findAll(), function(User $user) use ($input) {

                if ($input->getOption( 'validation-pending' ) && $user->getValidated()) return false;
                if ($input->getOption( 'validated' ) && !$user->getValidated()) return false;
                if ($input->getOption( 'mods' ) && !$user->getRightsElevation() >= User::ROLE_CROW) return false;

                return true;
            } );

            $table = new Table( $output );
            $table->setHeaders( ['ID', 'Name', 'Mail', 'Validated?', 'Mod?', 'ActCitID.','ValTkn.'] );

            foreach ($users as $user) {
                $activeCitizen = $this->entityManager->getRepository(Citizen::class)->findActiveByUser( $user );
                $pendingValidation = $user->getPendingValidation();
                $table->addRow( [
                    $user->getId(), $user->getUsername(), $user->getEmail(), $user->getValidated() ? '1' : '0',
                    $user->getRightsElevation() >= User::ROLE_CROW ? '1' : '0',
                    $activeCitizen ? $activeCitizen->getId() : '-',
                    $pendingValidation ? "{$pendingValidation->getPkey()} ({$pendingValidation->getType()})" : '-'
                ] );
            }

            $table->render();
            $output->writeln('Found a total of <info>' . count($users) . '</info> users.');
        }

        return 0;
    }
}