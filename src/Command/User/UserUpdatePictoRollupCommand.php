<?php

namespace App\Command\User;


use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RolePlayText;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Messages\Command\CommandMessage;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\Actions\User\UserPictoRollupAction;
use App\Service\CommandHelper;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsCommand(
    name: 'app:user:rollup:picto:update',
    description: 'Updates a users picto rollup.'
)]
class UserUpdatePictoRollupCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommandHelper $helper,
        private readonly UserPictoRollupAction $action,
        private readonly MessageBusInterface $bus
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to update a users picto rollup.')

            ->addOption('user', null,InputOption::VALUE_REQUIRED, 'Restrict to a specific user ID')
            ->addOption('picto', null, InputOption::VALUE_REQUIRED, 'Restrict to a specific picto ID')
            ->addOption('imported', null, InputOption::VALUE_REQUIRED, 'Set 0 to process only non-imported pictos or any other value to process only imported pictos.')
            ->addOption('old', null, InputOption::VALUE_REQUIRED, 'Set 0 to process only new pictos or any other value to process only old pictos.')
            ->addOption('dispatch', null, InputOption::VALUE_NONE, 'Dispatch execution to queue instead of processing synchronously.');
    }

    private function getPictoPrototype( ?int $pictoID ) {
        return $pictoID
            ? [$this->em->getRepository(PictoPrototype::class)->find($pictoID)]
            : $this->em->getRepository(PictoPrototype::class)->findAll();
    }

    private function getSeasons() {
        $criteria = new Criteria();

        return [null, ...$this->em->getRepository(Season::class)->matching(
            ($criteria)
                ->where( $criteria->expr()->gt('number', 0) )
                ->orWhere( $criteria->expr()->gte('subNumber', 15) )
        )->toArray()];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pictoID = $input->getOption('picto');
        $userID = $input->getOption('user');

        $use_imported = $input->getOption('imported') !== null  ? $input->getOption('imported') !== '0' : null;
        $use_old = $input->getOption('old') !== null ? $input->getOption('old') !== '0' : null;

        $picto_prototype = $this->getPictoPrototype( $pictoID );
        $seasons = $this->getSeasons();

        if (empty($picto_prototype) || $picto_prototype[0] === null)
            throw new \Exception('Picto not found.');

        if ($input->getOption('dispatch')) {

            $output->writeln('Dispatching...');

            if ($userID || $pictoID) throw new \Exception('Dispatch cannot be used with --user or --picto.');

            $command[] = 'app:user:rollup:picto:update';
            if ($use_imported !== null) $command[] = '--imported ' . ($use_imported ? '1' : '0');
            if ($use_old !== null) $command[] = '--old ' . ($use_old ? '1' : '0');

            $command = implode(' ', $command);
            foreach ($this->em->getRepository(User::class)->createQueryBuilder('u')->select('u.id')
                ->getQuery()->getSingleColumnResult() as $id)
                $this->bus->dispatch( new CommandMessage( "$command --user {$id}" ) );

        } else {
            $this->helper->leChunk($output, User::class, 10, $userID === null ? [] : ['id' => $userID], true, true, function(User $user) use ($use_old, $use_imported, &$picto_prototype, &$seasons) {

                foreach ($seasons as $season)
                    ($this->action)($user, $picto_prototype, $season, $use_imported, $use_old);

            }, true, function () use (&$picto_prototype, &$seasons, $pictoID) {

                $picto_prototype = $this->getPictoPrototype( $pictoID );
                $seasons = $this->getSeasons();

            });
        }


        return 0;
    }
}
