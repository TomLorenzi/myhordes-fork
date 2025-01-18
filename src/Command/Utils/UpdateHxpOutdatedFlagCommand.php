<?php


namespace App\Command\Utils;


use App\Command\LanguageCommand;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\HeroExperienceEntry;
use App\Entity\RuinExplorerStats;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Enum\HeroXPType;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:utils:hpx-outdate',
    description: 'Updates the outdated flag on HXP entries based on the min season setting. Must be run every time after this setting changes.'
)]
class UpdateHxpOutdatedFlagCommand extends LanguageCommand
{


    public function __construct(
        private readonly ConfMaster $confMaster,
        private readonly InvalidateTagsInAllPoolsAction $invalidateTagsInAllPoolsAction,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $min = $this->confMaster->getGlobalConf()->get( MyHordesSetting::HxpFirstSeason );
        $this->helper->leChunk( $output, HeroExperienceEntry::class, 500, [], true, false, function (HeroExperienceEntry $entry) use ($min) {

            $must_outdate = $entry->getType() !== HeroXPType::Legacy && $entry->getSeason()?->getNumber() < $min;
            if ($must_outdate !== $entry->isOutdated()) {
                $entry->setOutdated($must_outdate);
                return true;
            } else return false;

        }, true );

        ($this->invalidateTagsInAllPoolsAction)(['hxp']);

        return 0;
    }
}
