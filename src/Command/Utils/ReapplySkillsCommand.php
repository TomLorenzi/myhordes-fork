<?php


namespace App\Command\Utils;


use Adbar\Dot;
use App\Command\LanguageCommand;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\HeroSkillPrototype;
use App\Entity\RuinExplorerStats;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\Configuration\CitizenProperties;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\UserHandler;
use ArrayHelpers\Arr;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:utils:reapply-skills',
    description: 'Updates citizen properties to match latest skill configuration'
)]
class ReapplySkillsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Updates citizen properties to match latest skill configuration')

            ->addOption('town', 't',InputOption::VALUE_REQUIRED, 'Sets the town ID', -1)
            ->addOption('user', 'u',InputOption::VALUE_REQUIRED, 'Sets the user ID', -1)
            ->addOption('citizen', 'c',InputOption::VALUE_REQUIRED, 'Sets the citizen ID', -1)
            ->addOption('add', 'a',InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Adds skills by their ID', [])
            ->addOption('remove', 'r',InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Removes skills by their ID', [])

            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate process')
        ;
        parent::configure();
    }

    protected function fixSkillIDs(array $ids, array $add = [], array $remove = []): array {
        if (empty($add) && empty($remove)) return $ids;

        $ids = array_filter($ids, fn(int $id) => !in_array($id, $remove));
        $remove = [];

        foreach ($add as $id) {
            $skill = is_numeric($id)
                ? $this->entityManager->getRepository(HeroSkillPrototype::class)->find($id)
                : $this->entityManager->getRepository(HeroSkillPrototype::class)->findOneBy(['icon' => ["super_$id", $id]]);

            if (!$skill) continue;

            if (!$skill->isLegacy()) {
                $all_group_skills = $this->entityManager->getRepository(HeroSkillPrototype::class)->findBy(['groupIdentifier' => $skill->getGroupIdentifier()]);
                foreach ($all_group_skills as $s)
                    if ($s->getLevel() < $skill->getLevel()) $remove[] = $s->getId();
                    elseif ($s->getLevel() > $skill->getLevel() && in_array( $s->getId(), $ids ) ) continue(2);

                $ids = array_filter($ids, fn(int $id) => !in_array($id, $remove));
                $ids[] = $skill->getId();
                $remove = [];
            }
        }


        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $criteria = (new Criteria())->where(Criteria::expr()->eq('alive', true));
        if (($town = (int)$input->getOption('town')) > 0)
            $criteria->andWhere( Criteria::expr()->eq('town', $this->entityManager->getRepository(Town::class)->find( $town ) ) );

        if (($user = (int)$input->getOption('user')) > 0)
            $criteria->andWhere( Criteria::expr()->eq('user', $this->entityManager->getRepository(User::class)->find( $user ) ) );

        if (($citizen = (int)$input->getOption('citizen')) > 0)
            $criteria->andWhere( Criteria::expr()->eq('id', $citizen ) );

        $dryRun = (bool)$input->getOption('dry-run');

        $citizens = $this->entityManager->getRepository(Citizen::class)->matching($criteria)->map( fn(Citizen $c) => $c->getId() );
        foreach ($citizens as $citizenId) {

            $this->entityManager->clear();

            $citizen = $this->entityManager->getRepository(Citizen::class)->find($citizenId);
            if (!$citizen) {
                $output->writeln("<fg=red>Citizen #{$citizenId} not found.</>");
                continue;
            }

            if ($citizen->getProfession()->getName() === CitizenProfession::DEFAULT) {
                $output->writeln("<fg=yellow>Citizen #{$citizenId} ({$citizen->getName()}) has not been onboarded yet.</>");
                continue;
            }

            if (!$citizen->getProperties()) {
                $output->writeln("<fg=yellow>Citizen #{$citizenId} ({$citizen->getName()}, {$citizen->getProfession()->getLabel()}) has no properties object associated.</>");
                continue;
            }

            $selected_skill_ids = $citizen->property( CitizenProperties::ActiveSkillIDs );
            if (empty($selected_skill_ids)) {
                $output->writeln("<fg=yellow>Citizen #{$citizenId} ({$citizen->getName()}, {$citizen->getProfession()->getLabel()}) has no recorded skill IDs.</>");
                continue;
            }

            $selected_skill_ids = $this->fixSkillIDs($selected_skill_ids, $input->getOption('add'), $input->getOption('remove'));
            $skills = $this->entityManager->getRepository(HeroSkillPrototype::class)->findBy(['id' => $selected_skill_ids]);
            if (count($skills) !== count($selected_skill_ids)) {
                $output->writeln("<fg=red>Unable to fetch all skills: " . implode(',', $selected_skill_ids) . "</>");
                continue;
            }

            $existing_props = new Dot( $citizen->getProperties()->getProps() );
            $updated_props  = new Dot( );
            $updated_props->set( CitizenProperties::ActiveSkillIDs->value, $selected_skill_ids );

            foreach ($skills as $skill)
                foreach ($skill->getCitizenProperties() ?? [] as $propPath => $value)
                    $updated_props->set(
                        $propPath,
                        \App\Enum\Configuration\CitizenProperties::from($propPath)->merge(
                            $updated_props->get($propPath),
                            $value
                        )
                    );

            $keys = array_map(fn(\App\Enum\Configuration\CitizenProperties $c) => $c->value,\App\Enum\Configuration\CitizenProperties::validCases());

            $table = [];
            $change = false;
            foreach ($keys as $key) {

                if (!$existing_props->has($key) && !$updated_props->has($key)) continue;

                $both_exist = $existing_props->has($key) && $updated_props->has($key);
                $prev = json_encode($existing_props->get($key));
                $now = json_encode($updated_props->get($key));

                //if ($prev === $now) continue;

                if ($prev === $now && $both_exist) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) $table[] = [$key, $prev, $now];
                } else {
                    $change = true;
                    if ($prev !== $now && $both_exist) $table[] = ["<fg=yellow>$key</>", $prev, $now];
                    elseif (!$existing_props->has($key)) $table[] = ["<fg=green>$key</>", '', $now];
                    else $table[] = ["<fg=red>$key</>", $prev, ''];
                }

            }

            if (empty($table)) {
                $output->writeln("<fg=yellow>Citizen #{$citizenId} ({$citizen->getName()}) has no changes.</>");
            } else {
                $output->writeln("Citizen #{$citizenId} ({$citizen->getName()})");
                $io = new SymfonyStyle($input, $output);
                $io->table(['Property', 'Old', 'New'], $table);
            }

            if (!$dryRun && $change) {
                $this->entityManager->persist( $citizen->getProperties()->setProps( $updated_props->all() ) );
                $this->entityManager->flush();
            }

            $this->entityManager->clear();
        }

        return 0;
    }
}
