<?php

namespace App\Command\User;


use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\FoundRolePlayText;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RolePlayText;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\Actions\User\UserPictoRollupAction;
use App\Service\CommandHelper;
use App\Service\UserHandler;
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
            ->addOption('old', null, InputOption::VALUE_REQUIRED, 'Set 0 to process only new pictos or any other value to process only old pictos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pictoID = $input->getOption('picto');
        $userID = $input->getOption('user');

        $use_imported = $input->getOption('imported') !== null  ? $input->getOption('imported') !== '0' : null;
        $use_old = $input->getOption('old') !== null ? $input->getOption('old') !== '0' : null;

        $picto_prototype = $pictoID
            ? [$this->em->getRepository(PictoPrototype::class)->find($pictoID)]
            : $this->em->getRepository(PictoPrototype::class)->findAll();

        if (empty($picto_prototype) || $picto_prototype[0] === null)
            throw new \Exception('Picto not found.');

        $this->helper->leChunk($output, User::class, 100, $userID === null ? [] : ['id' => $userID], true, true, function(User $user) use ($use_old, $use_imported, &$picto_prototype) {
            ($this->action)($user, $picto_prototype, $use_imported, $use_old);
        }, true, function () use (&$picto_prototype, $pictoID) {
            $picto_prototype = $pictoID
                ? [$this->em->getRepository(PictoPrototype::class)->find($pictoID)]
                : $this->em->getRepository(PictoPrototype::class)->findAll();
        });

        return 0;
    }
}
