<?php

namespace App\Command;


use App\Entity\Avatar;
use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Service\CommandHelper;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserInfoCommand extends Command
{
    protected static $defaultName = 'app:users';

    private $entityManager;
    private $user_handler;
    private $pwenc;
    private $helper;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserHandler $uh, CommandHelper $ch)
    {
        $this->entityManager = $em;
        $this->pwenc = $passwordEncoder;
        $this->user_handler = $uh;
        $this->helper = $ch;
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

            ->addOption('find-all-rps', null, InputOption::VALUE_REQUIRED, 'Gives all known RP to an user in the given lang')
            ->addOption('give-all-pictos', null, InputOption::VALUE_REQUIRED, 'Gives all pictos once to an user')
            ->addOption('give-one-picto', null, InputOption::VALUE_REQUIRED, 'Gives one specific picto to an user')
            ->addOption('remove-all-pictos', null, InputOption::VALUE_REQUIRED, 'Remove all pictos once to an user')
            ->addOption('remove-one-picto', null, InputOption::VALUE_REQUIRED, 'Remove one specific picto once to an user')
            ->addOption('find-all-rps', null, InputOption::VALUE_REQUIRED, 'Gives all known RP to a user in the given lang')

            ->addOption('set-mod-level', null, InputOption::VALUE_REQUIRED, 'Sets the moderation level for a user (0 = normal user, 2 = oracle, 3 = mod, 4 = admin)')
            ->addOption('set-hero-days', null, InputOption::VALUE_REQUIRED, 'Set the amount of hero days spent to a user (and the associated skills)')

            ->addOption('set-avatar',   'a',   InputOption::VALUE_REQUIRED, 'Enter a local file name to use as an avatar for this user.')
            ->addOption('remove-avatar', null,  InputOption::VALUE_NONE,  'Removes a user avatar.')
            ->addOption('avatar-ext',   null,  InputOption::VALUE_OPTIONAL, 'Specify the file extension for --set-avatar. If omitted, the extension will be automatically determined.')
            ->addOption('avatar-small', null,  InputOption::VALUE_NONE,     'If used with --set-avatar, the given avatar will be used as small avatar if a normal avatar is already set. If used with --remove-avatar, only the small avatar will be deleted.')
            ->addOption('avatar-x',     null,  InputOption::VALUE_REQUIRED, 'Sets the image width. Should be set when Imagick is not available. Has no effect when uploading a small avatar.')
            ->addOption('avatar-y',     null,  InputOption::VALUE_REQUIRED, 'Sets the image height. Should be set when Imagick is not available. Has no effect when uploading a small avatar.')
            ->addOption('avatar-magick', null,  InputOption::VALUE_REQUIRED, 'When setting an avatar, "auto" will attempt to use Imagick (default), "force" will enforce Imagick and "raw" will disable Imagick.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($userid = $input->getArgument('UserID')) {
            /** @var User $user */
            $user = $this->helper->resolve_string($userid, User::class, 'User', $this->getHelper('question'), $input, $output);

            if ($user === null) throw new \Exception('User not found.');

            $helper = $this->getHelper('question');

            if (null !== ($modlv = $input->getOption('set-mod-level'))) {
                $user->setRightsElevation($modlv);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($rpLang = $input->getOption('find-all-rps')) {
                $rps = $this->entityManager->getRepository(RolePlayText::class)->findAllByLang($rpLang);
                $count = 0;
                foreach ($rps as $rp) {
                    $alreadyfound = $this->entityManager->getRepository(FoundRolePlayText::class)->findByUserAndText($user, $rp);
                    if (null !== $alreadyfound) {
                        continue;
                    }
                    ++$count;
                    $foundrp = new FoundRolePlayText();
                    $foundrp->setUser($user)->setText($rp);
                    $user->getFoundTexts()->add($foundrp);

                    $this->entityManager->persist($foundrp);
                }
                echo "Added {$count} RPs to user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($count = $input->getOption('give-all-pictos')) {
                $question = new Question('Please enter a town ID to bind the pictos to (default: none): ');
                $town = null;
                $townId = $helper->ask($input, $output, $question);
                if (null !== $townId) {
                    $town = $this->entityManager->getRepository(Town::class)->find($townId);
                    if (null === $town) {
                        echo "{$townId} is not a valid town\n";

                        return 1;
                    }
                }
                $pictoPrototypes = $this->entityManager->getRepository(PictoPrototype::class)->findAll();
                foreach ($pictoPrototypes as $pictoPrototype) {
                    $picto = $this->entityManager->getRepository(Picto::class)->findByUserAndTownAndPrototype($user, $town, $pictoPrototype);
                    if (null === $picto) {
                        $picto = new Picto();
                        $picto->setPrototype($pictoPrototype)
                            ->setPersisted(2)
                            ->setTown($town)
                            ->setTownEntry(null !== $town ? $town->getRankingEntry() : null)
                            ->setUser($user);
                    }
                    $picto->setCount($picto->getCount() + $count);

                    $this->entityManager->persist($picto);
                }
                echo "+ {$count} to all pictos of user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($pictoName = $input->getOption('give-one-picto')) {
                $pictoPrototype = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoName]);
                if (null === $pictoPrototype) {
                    echo "{$pictoName} is not a valid picto !\n";

                    return 1;
                }
                $question = new Question('Please enter a town ID to bind the picto to (default: none): ');
                $town = null;
                $townId = $helper->ask($input, $output, $question);
                if (null !== $townId) {
                    $town = $this->entityManager->getRepository(Town::class)->find($townId);
                    if (null === $town) {
                        echo "{$townId} is not a valid town\n";

                        return 1;
                    }
                }

                $question = new Question('How many picto should we remove ? (1) ', 1);
                $count = $helper->ask($input, $output, $question);
                if (intval($count) <= 0) {
                    $count = 1;
                }

                $picto = $this->entityManager->getRepository(Picto::class)->findByUserAndTownAndPrototype($user, $town, $pictoPrototype);
                if (null === $picto) {
                    $picto = new Picto();
                    $picto->setPrototype($pictoPrototype)
                        ->setPersisted(2)
                        ->setTown($town)
                        ->setTownEntry(null !== $town ? $town->getRankingEntry() : null)
                        ->setUser($user);
                    $user->addPicto($picto);
                    $this->entityManager->persist($user);
                }

                $picto->setCount($picto->getCount() + $count);
                echo "$count picto {$pictoName} gived to user {$user->getUsername()}\n";

                $this->entityManager->persist($picto);
                $this->entityManager->flush();
            } elseif ($count = $input->getOption('remove-all-pictos')) {
                $question = new Question('Please enter a town ID to remove the picto from (default: all): ', 'all');
                $town = null;
                $townId = $helper->ask($input, $output, $question);
                if (null !== $townId) {
                    $town = $this->entityManager->getRepository(Town::class)->find($townId);
                    if (null === $town) {
                        echo "{$townId} is not a valid town\n";

                        return 1;
                    }
                }

                $pictoPrototypes = $this->entityManager->getRepository(PictoPrototype::class)->findAll();
                foreach ($pictoPrototypes as $pictoPrototype) {
                    $pictos = $this->entityManager->getRepository(Picto::class)->findBy(['user' => $user, 'prototype' => $pictoPrototype, 'town' => $town]);
                    if (count($pictos) > 0) {
                        $toRemove = $count;
                        for ($i = 0; $i < count($pictos) && $toRemove > 0; ++$i) {
                            $picto = $pictos[$i];
                            if ($picto->getCount() - $toRemove <= 0) {
                                $toRemove -= $picto->getCount();
                                $user->removePicto($picto);
                                $this->entityManager->remove($picto);
                            } else {
                                $picto->setCount($picto->getCount() - $toRemove);
                                $toRemove = 0;
                                $this->entityManager->persist($picto);
                            }
                        }
                    }
                }
                echo "- {$count} to all pictos of user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($pictoName = $input->getOption('remove-one-pictos')) {
                $pictoPrototype = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoName]);
                if (null === $pictoPrototype) {
                    echo "{$pictoName} is not a valid picto !\n";

                    return 1;
                }

                $question = new Question('Please enter a town ID to remove the picto from (default: all): ', 'all');
                $town = null;
                $townId = $helper->ask($input, $output, $question);
                if (null !== $townId && 'all' !== $townId) {
                    $town = $this->entityManager->getRepository(Town::class)->find($townId);
                    if (null === $town) {
                        echo "{$townId} is not a valid town\n";

                        return 1;
                    }
                }

                $question = new Question('How many picto should we remove ? (1) ', 1);
                $count = $helper->ask($input, $output, $question);
                if (intval($count) <= 0) {
                    $count = 1;
                }

                $filter = ['user' => $user, 'prototype' => $pictoPrototype];

                if (null !== $town) {
                    $filter['town'] = $town;
                }

                $pictos = $this->entityManager->getRepository(Picto::class)->findBy($filter);
                if (count($pictos) > 0) {
                    $toRemove = $count;
                    for ($i = 0; $i < count($pictos) && $toRemove > 0; ++$i) {
                        $picto = $pictos[$i];
                        if ($picto->getCount() - $toRemove <= 0) {
                            $toRemove -= $picto->getCount();
                            $user->removePicto($picto);
                            $this->entityManager->remove($picto);
                        } else {
                            $picto->setCount($picto->getCount() - $toRemove);
                            $toRemove = 0;
                            $this->entityManager->persist($picto);
                        }
                    }
                }
                echo "- {$count} to the picto {$pictoName} of user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($newpw = $input->getOption('set-password')) {
                if ('auto' === $newpw) {
                    $newpw = '';
                    $source = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789-_$';
                    for ($i = 0; $i < 9; ++$i) {
                        $newpw .= $source[mt_rand(0, strlen($source) - 1)];
                    }
                }

                $user->setPassword($this->pwenc->encodePassword($user, $newpw));
                $output->writeln("New password set: <info>{$newpw}</info>");
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
                    case "force":
                        $m = UserHandler::ImageProcessingForceImagick;
                        break;
                    case "raw":
                        $m = UserHandler::ImageProcessingDisableImagick;
                        break;
                    default:
                        $m = UserHandler::ImageProcessingPreferImagick;
                        break;
                }

                $ext = strtolower($input->getOption('avatar-ext') ?: pathinfo($avatar, PATHINFO_EXTENSION));
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
            $users = array_filter($this->entityManager->getRepository(User::class)->findAll(), function (User $user) use ($input) {
                if ($input->getOption('validation-pending') && $user->getValidated()) {
                    return false;
                }
                if ($input->getOption('validated') && !$user->getValidated()) {
                    return false;
                }
                if ($input->getOption('mods') && !$user->getRightsElevation() >= User::ROLE_CROW) {
                    return false;
                }

                return true;
            });

            $table = new Table($output);
            $table->setHeaders(['ID', 'Name', 'Mail', 'Validated?', 'Mod?', 'ActCitID.', 'ValTkn.']);

            foreach ($users as $user) {
                $activeCitizen = $this->entityManager->getRepository(Citizen::class)->findActiveByUser($user);
                $pendingValidation = $user->getPendingValidation();
                $table->addRow([
                    $user->getId(), $user->getUsername(), $user->getEmail(), $user->getValidated() ? '1' : '0',
                    $user->getRightsElevation() >= User::ROLE_CROW ? '1' : '0',
                    $activeCitizen ? $activeCitizen->getId() : '-',
                    $pendingValidation ? "{$pendingValidation->getPkey()} ({$pendingValidation->getType()})" : '-',
                ]);
            }

            $table->render();
            $output->writeln('Found a total of <info>' . count($users) . '</info> users.');
        }

        return 0;
    }
}
