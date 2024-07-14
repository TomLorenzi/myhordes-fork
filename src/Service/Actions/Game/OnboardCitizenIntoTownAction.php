<?php

namespace App\Service\Actions\Game;

use App\Controller\Soul\SoulController;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
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
use Doctrine\ORM\EntityManagerInterface;
use Exception;

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
    ) { }

    /**
     * @param Citizen $citizen
     * @param CitizenProfession $profession
     * @param string|null $alias
     * @return bool
     */
    public function __invoke(
        Citizen $citizen,
        CitizenProfession $profession,
        ?string $alias = null,
    ): bool
    {
        if ($citizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return false;

        $town = $citizen->getTown();
        $citizen->setAlias( $alias );

        $this->citizenHandler->applyProfession( $citizen, $profession );
        $inventory = $citizen->getInventory();

        if($profession->getHeroic()) {
            $skills = $this->entityManager->getRepository(HeroSkillPrototype::class)->getUnlocked($citizen->getUser()->getAllHeroDaysSpent());

            if ($this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', true ) ) {
                $item = ($this->itemFactory->createItem( "photo_3_#00" ))->setEssential(true);
                $this->proxy->transferItem($citizen, $item, to: $inventory);
            }

            /** @var HeroSkillPrototype $skill */
            foreach ($skills as $skill) {
                switch($skill->getName()){
                    case "largechest1":
                    case "largechest2":
                        $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + 1);
                        break;
                    case 'apag':
                        // Only give the APAG via Hero XP if it is not unlocked via Soul Inventory
                        if (!$this->userHandler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', false ) ) {
                            $item = ($this->itemFactory->createItem( "photo_3_#00" ))->setEssential(true);
                            $this->proxy->transferItem($citizen, $item, to: $inventory);
                        }
                        break;
                }

                // If we have Start Items linked to the Skill, add them to the chest
                if ($skill->getStartItems()->count() > 0) {
                    foreach ($skill->getStartItems() as $prototype) {
                        $this->inventoryHandler->forceMoveItem($citizen->getHome()->getChest(), $this->itemFactory->createItem($prototype));
                    }
                }

                // If the HeroSkill unlocks a Heroic Action, give it
                if ($skill->getUnlockedAction()) {
                    $previouslyUsed = false;
                    // A heroic action can replace one. Let's handle it!
                    if ($skill->getUnlockedAction()->getReplacedAction() !== null) {
                        $proto = $this->entityManager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $skill->getUnlockedAction()->getReplacedAction()]);
                        $previouslyUsed = $citizen->getUsedHeroicActions()->contains($proto);
                        $citizen->removeHeroicAction($proto);
                        $citizen->removeUsedHeroicAction($proto);
                    }
                    if ($previouslyUsed)
                        $citizen->addUsedHeroicAction($skill->getUnlockedAction());
                    else
                        $citizen->addHeroicAction($skill->getUnlockedAction());
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

        return true;
    }
}