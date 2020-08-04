<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\User;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
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
    private $pwenc;
    
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $em;
        $this->pwenc = $passwordEncoder;
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

            ->addOption('set-mod-level', null, InputOption::VALUE_REQUIRED, 'Sets the moderation level for an user (0 = normal user, 2 = oracle, 3 = mod, 4 = admin)')
            ->addOption('set-hero-days', null, InputOption::VALUE_REQUIRED, 'Set the amount of hero days spent to an user (and the associated skills)')
        ;
        
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($userid = $input->getArgument('UserID')) {
            $userid = (int)$userid;
            /** @var User $user */
            $user = $this->entityManager->getRepository(User::class)->find($userid);
            
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
                    if($picto === null) {
                        $picto = new Picto();
                        $picto->setPrototype($pictoPrototype)
                        ->setPersisted(2)
                        ->setTown(null)
                        ->setTownEntry(null)
                        ->setUser($user);
                    }
                    $picto->setCount($picto->getCount()+$count);
                    
                    $this->entityManager->persist($picto);
                }
                echo "+ $count to all pictos of user {$user->getUsername()}\n";
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($pictoName = $input->getOption('give-one-picto')) {
                $pictoPrototype = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoName]);
                if($pictoPrototype === null) {
                    echo "$pictoName is not a valid picto !\n";
                    return 1;
                }
                $picto = $this->entityManager->getRepository(Picto::class)->findByUserAndTownAndPrototype($user, null, $pictoPrototype);
                if ($picto === null) {
                    $picto = new Picto();
                    $picto->setPrototype($pictoPrototype)
                        ->setPersisted(2)
                        ->setTown(null)
                        ->setTownEntry(null)
                        ->setUser($user);
                    $user->addPicto($picto);
                }

                $picto->setCount($picto->getCount()+1);
                echo "Picto $pictoName gived to user {$user->getUsername()}\n";

                $this->entityManager->persist($picto);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } elseif ($count = $input->getOption('remove-all-pictos')) {
                $pictoPrototypes = $this->entityManager->getRepository(PictoPrototype::class)->findAll();
                foreach ($pictoPrototypes as $pictoPrototype) {
                    $pictos = $this->entityManager->getRepository(Picto::class)->findBy(["user" => $user, 'prototype' => $pictoPrototype]);
                    if(count($pictos) > 0) {
                        $toRemove = $count;
                        for($i = 0; $i < count($pictos) && $toRemove > 0 ; $i++) {
                            $picto = $pictos[$i];
                            if($picto->getCount() - $toRemove <= 0) {
                                $toRemove -= $picto->getCount();
                                $user->removePicto($picto);
                                $this->entityManager->remove($picto);
                            }
                            else {
                                $picto->setCount($picto->getCount() - $toRemove);
                                $toRemove = 0;
                                $this->entityManager->persist($picto);
                            }
                        }
                    }
                }
                echo "- $count to all pictos of user {$user->getUsername()}\n";
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
    