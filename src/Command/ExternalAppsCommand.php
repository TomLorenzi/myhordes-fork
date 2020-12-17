<?php


namespace App\Command;

use App\Entity\ExternalApp;
use App\Entity\User;
use App\Service\CommandHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Url as UrlConstraint;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExternalAppsCommand extends Command
{
    protected static $defaultName = 'app:external-apps';

    private $entityManager;
    private $helper;
    private $validator;

    public function __construct(EntityManagerInterface $em, CommandHelper $ch, ValidatorInterface $validator)
    {
        $this->entityManager = $em;
        $this->helper = $ch;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manage external apps.')
            ->setHelp('This command helps manage external apps.')

            ->addArgument('ExternalAppId', InputArgument::OPTIONAL, 'The identifier of the external app')

            ->addOption('new', null, InputOption::VALUE_NONE, 'Create a new External app')

        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('ExternalAppId');
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Active', 'Owner', 'URL', 'Icon', 'Secret', 'Contact', 'Testing', 'LinkOnly']);

        if ($id !== null && $id > 0) {
            /** @var ExternalApp $app */
            $app = $this->entityManager->getRepository(ExternalApp::class)->find($id);
            if($app === null) throw new \Exception('External app not found.');

            $table->addRow([
                $app->getId(),
                $app->getName(),
                $app->getActive(),
                $app->getOwner() !== null ? $app->getOwner()->getUsername() : "NULL",
                $app->getUrl(),
                $app->getIcon(),
                $app->getSecret(),
                $app->getContact(),
                $app->getTesting(),
                $app->getLinkOnly()
            ]);
        } else if ($input->getOption('new')) {
            $helper = $this->getHelper('question');

            $question = new Question('What is the name of the external app: ');
            $appName = $helper->ask($input, $output, $question);
            if(empty(trim($appName))) {
                throw new \Exception("Name is mandatory");
            }

            $question = new Question('What is the URL of the external app : ');
            $url = $helper->ask($input, $output, $question);
            if(empty(trim($url))) {
                throw new \Exception("URL is mandatory");
            }

            $constraint = new UrlConstraint();
            $constraint->message = 'The URL is invalid';

            $errors = $this->validator->validate(
                $url,
                $constraint
            );

            if(count($errors) > 0){
                throw new \Exception($errors);
            }

            $question = new Question('What is the contact email of the external app : ');
            $contact = $helper->ask($input, $output, $question);
            if(empty(trim($contact))) {
                throw new \Exception("Contact is mandatory");
            }

            $constraint = new EmailConstraint();
            $constraint->message = 'The contact email is invalid';

            $errors = $this->validator->validate(
                $contact,
                $constraint
            );

            if(count($errors) > 0){
                throw new \Exception($errors);
            }

            $question = new Question('Is the external app active (Y/n): ');
            $active = $helper->ask($input, $output, $question);
            if (strtolower($active) == "n") {
                $active = 0;
            } else {
                $active = 1;
            }

            $question = new Question('Who is the owner of the app (empty for no-one, ID or username can be used): ');
            $ownerId = $helper->ask($input, $output, $question);
            $owner = null;
            if (!empty(trim($ownerId))) {
                /** @var User $user */
                $owner = $this->helper->resolve_string($ownerId, User::class, 'User', $this->getHelper('question'), $input, $output);
                if (!$owner) {
                    $output->writeln("<error>The selected user could not be found.</error>");
                    return 1;
                }
            }

            $question = new Question('Is the external app a testing app (y/N): ');
            $testing = $helper->ask($input, $output, $question);
            if (strtolower($testing) == "y") {
                $testing = 1;
            } else {
                $testing = 0;
            }

            $question = new Question('Link Only (y/N): ');
            $linkonly = $helper->ask($input, $output, $question);
            if (strtolower($linkonly) == "n") {
                $linkonly = 0;
            } else {
                $linkonly = 1;
            }

            $x = '';
            for($i = 1; $i <= 16; $i++){
                $x .= dechex(random_int(0,255));
            }
            $pkey = substr($x, 0, 16);

            $newApp = (new ExternalApp())
                        ->setName($appName)
                        ->setContact($contact)
                        ->setActive($active)
                        ->setOwner($owner)
                        ->setSecret($pkey)
                        ->setUrl($url)
                        ->setLinkOnly($linkonly)
                        ->setTesting($testing);

            $table->addRow([
                $newApp->getId(),
                $newApp->getName(),
                $newApp->getActive(),
                $newApp->getOwner() !== null ? $newApp->getOwner()->getUsername() : "NULL",
                $newApp->getUrl(),
                $newApp->getSecret(),
                $newApp->getContact(),
                $newApp->getTesting(),
                $newApp->getLinkOnly()
            ]);

            $this->entityManager->persist($newApp);

        } else {
            
            $apps = $this->entityManager->getRepository(ExternalApp::class)->findAll();
            foreach($apps as $app){
                /** @var ExternalApp $app */
                $table->addRow([
                    $app->getId(),
                    $app->getName(),
                    $app->getActive(),
                    $app->getOwner() !== null ? $app->getOwner()->getUsername() : "NULL",
                    $app->getUrl(),
                    $app->getSecret(),
                    $app->getContact(),
                    $app->getTesting(),
                    $app->getLinkOnly()
                ]);
            }            
        }
        $this->entityManager->flush();

        $table->render();
        
        return 0;
    }
}
