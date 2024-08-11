<?php

namespace App\Service\Actions\Game;

use App\Controller\Soul\SoulController;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenProperties;
use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\UserHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class OnboardCitizenIntoTownAction
{
    public function __construct(
        private ConfMaster $confMaster,
        private CitizenHandler $citizenHandler,
        private EntityManagerInterface $entityManager,
        private UserHandler $userHandler,
        private ItemFactory $itemFactory,
        private EventProxyService $proxy,
        private InventoryHandler $inventoryHandler,
        private HubInterface $hub,
        private CountCitizenProfessionsAction $counter,
        private SpanHeroicActionInheritanceTreeAction $inheritanceTreeAction,
    ) { }

    /**
     * @param Citizen $citizen
     * @param CitizenProfession $profession
     * @param string|null $alias
     * @param HeroSkillPrototype[] $heroSkills
     * @return bool
     */
    public function __invoke(
        Citizen $citizen,
        CitizenProfession $profession,
        ?string $alias = null,
        array $heroSkills = [],
    ): bool
    {
        if ($citizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return false;

        $citizenPropConfig = [];

        $town = $citizen->getTown();
        $citizen->setAlias( $alias );

        $this->citizenHandler->applyProfession( $citizen, $profession );
        $inventory = $citizen->getInventory();

        if($profession->getHeroic()) {
            if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', true ) ) {
                $item = ($this->itemFactory->createItem( "photo_3_#00" ))->setEssential(true);
                $this->proxy->transferItem($citizen, $item, to: $inventory);
            }

            foreach ($heroSkills as $skill) {

                if ($feature = $skill->getInhibitedBy()) {
                    if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), $feature, false ) )
                        continue;
                }

                foreach ($skill->getCitizenProperties() ?? [] as $propPath => $value)
                    Arr::set(
                        $citizenPropConfig,
                        $propPath,
                        \App\Enum\Configuration\CitizenProperties::from($propPath)->merge(Arr::get(
                            $citizenPropConfig,
                            $propPath
                        ), $value));

                // Grant chest space
                if ($skill->getGrantsChestSpace() > 0)
                    $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + $skill->getGrantsChestSpace());

                // If we have Start Items linked to the Skill, add them to the chest
                if ($skill->getStartItems()->count() > 0) {
                    foreach ($skill->getStartItems() as $prototype) {

                        if ($skill->isProfessionItems() || in_array( $prototype->getName(), $skill->getEssentialItemTypes() ?? [] )) {
                            $item = ($this->itemFactory->createItem( $prototype ))->setEssential(true);
                            $this->proxy->transferItem($citizen, $item, to: $inventory);
                        } else $this->inventoryHandler->forceMoveItem($citizen->getHome()->getChest(), $this->itemFactory->createItem($prototype));
                    }
                }

                // If the HeroSkill unlocks a Heroic Action, give it
                foreach ($skill->getUnlockedActions() as $unlockedAction) {
                    $previouslyUsed = false;
                    // A heroic action can replace others. Let's handle it!
                    foreach (($this->inheritanceTreeAction)($unlockedAction, -1) as $proto) {
                        $previouslyUsed = $previouslyUsed || $citizen->getUsedHeroicActions()->contains($proto);
                        $citizen->removeHeroicAction($proto);
                        $citizen->removeUsedHeroicAction($proto);
                    }

                    if ($previouslyUsed)
                        $citizen->addUsedHeroicAction($unlockedAction);
                    else
                        $citizen->addHeroicAction($unlockedAction);
                    $this->entityManager->persist($citizen);
                }
            }
        }

        if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_alarm', true ) ) {
            $item = ($this->itemFactory->createItem( "alarm_off_#00" ))->setEssential(true);
            $this->proxy->transferItem($citizen, $item, to: $inventory);
        }

        if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_arma', true ) ) {
            $armag_day   = $this->entityManager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_d"]);
            $armag_night = $this->entityManager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_n"]);
            $citizen->addSpecialAction($armag_day);
            $citizen->addSpecialAction($armag_night);
            $this->inventoryHandler->forceMoveItem($citizen->getHome()->getChest(), $this->itemFactory->createItem( 'food_armag_#00' ));
            $doggy = $this->inventoryHandler->fetchSpecificItems( $citizen->getHome()->getChest(), [new ItemRequest('food_bag_#00')] );
            if (!empty($doggy)) $this->inventoryHandler->forceRemoveItem($doggy[0]);
        }

        $vote_shaman = $this->entityManager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_vote_shaman"]);
        $vote_guide = $this->entityManager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_vote_guide"]);
        if ($vote_shaman) $citizen->addSpecialAction($vote_shaman);
        if ($vote_guide) $citizen->addSpecialAction($vote_guide);

        if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_wtns', true ) )
            $this->citizenHandler->inflictStatus($citizen, 'tg_infect_wtns');

        $citizen->setProperties( (new CitizenProperties())->setProps( $citizenPropConfig ) );

        try {
            $this->entityManager->persist( $citizen );
            $this->entityManager->flush();
        } catch (Exception $e) {
            return false;
        }

        $item_spawns = $this->confMaster->getTownConfiguration($town)->get(TownConf::CONF_DEFAULT_CHEST_ITEMS, []);
        $chest = $citizen->getHome()->getChest();

        foreach ($item_spawns as $spawn)
            $this->proxy->placeItem($citizen, $this->itemFactory->createItem($spawn), [$chest]);
        try {
            $this->entityManager->persist( $chest );
            $this->entityManager->flush();
        } catch (Exception $e) {}

        try {
            $this->hub->publish(new Update(
                topics: "myhordes://live/concerns/town-lobby/{$town->getId()}",
                data: json_encode([
                    'message' => 'citizen-count-update',
                    'list' => ($this->counter)($town)
                ]),
                private: true
            ));
        } catch (\Throwable $t) {}

        return true;
    }
}