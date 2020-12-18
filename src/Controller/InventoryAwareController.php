<?php

namespace App\Controller;

use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\ExpeditionRoute;
use App\Entity\FoundRolePlayText;
use App\Entity\HelpNotificationMarker;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\Recipe;
use App\Entity\SpecialActionPrototype;
use App\Entity\TownLogEntry;
use App\Entity\User;
use App\Entity\Zone;
use App\Interfaces\RandomGroup;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\PictoHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Structures\BankItem;
use App\Structures\ItemRequest;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryAwareController extends CustomAbstractController
    implements GameInterfaceController, GameProfessionInterfaceController, GameAliveInterfaceController, HookedInterfaceController
{
    protected DeathHandler $death_handler;
    protected ActionHandler $action_handler;
    protected PictoHandler $picto_handler;
    protected LogTemplateHandler $log;
    protected RandomGenerator $random_generator;
    protected ZoneHandler $zone_handler;
    protected LogTemplateHandler $logTemplateHandler;
    protected UserHandler $user_handler;
    protected CrowService $crow;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, DeathHandler $dh, PictoHandler $ph,
        TranslatorInterface $translator, LogTemplateHandler $lt, TimeKeeperService $tk, RandomGenerator $rd, ConfMaster $conf,
        ZoneHandler $zh, UserHandler $uh, CrowService $armbrust)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->action_handler = $ah;
        $this->picto_handler = $ph;
        $this->log = $lt;
        $this->random_generator = $rd;
        $this->zone_handler = $zh;
        $this->death_handler = $dh;
        $this->logTemplateHandler = $lt;
        $this->user_handler = $uh;
        $this->crow = $armbrust;
    }

    public function before(): bool
    {
        if ($this->citizen_handler->hasRole($this->getActiveCitizen(), 'ghoul') && !$this->getActiveCitizen()->hasSeenHelpNotification('ghoul')) {
            $this->addFlash('popup-ghoul', $this->renderView('ajax/game/notifications/ghoul.html.twig'));
            $this->getActiveCitizen()->addHelpNotification( $this->entity_manager->getRepository(HelpNotificationMarker::class)->findOneByName('ghoul') );
            $this->entity_manager->persist($this->getActiveCitizen());
            $this->entity_manager->flush();
        }
        return true;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null, $locale = null ): array {
        $data = parent::addDefaultTwigArgs($section, $data, $locale);
        $data['menu_section'] = $section;

        return $data;
    }

    protected function renderLog( ?int $day, $citizen = null, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];

        /** @var TownLogEntry $entity */
        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter(
            $this->getActiveCitizen()->getTown(),
            $day, $citizen, $zone, $type, $max ) as $idx=>$entity) {

                /** @var LogEntryTemplate $template */
                $template = $entity->getLogEntryTemplate();
                if (!$template)
                    continue;
                $entityVariables = $entity->getVariables();
                if($citizen !== null && $entity->getHidden())
                    continue;
                $entries[$idx]['timestamp'] = $entity->getTimestamp();
                $entries[$idx]['class'] = $template->getClass();
                $entries[$idx]['type'] = $template->getType();
                $entries[$idx]['id'] = $entity->getId();
                $entries[$idx]['hidden'] = $entity->getHidden();

                $variableTypes = $template->getVariableTypes();
                $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);
                try {
                    $entries[$idx]['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (Exception $e) {
                    $entries[$idx]['text'] = "null";
                }
            }
        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $entries,
            'canHideEntry' => $this->getActiveCitizen()->getAlive() && $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getUser(), 'manipulator') && $this->getActiveCitizen()->getZone() === null,
        ] );
    }

    /**
     * @param Inventory[] $inventories
     * @param ItemTargetDefinition $definition
     * @return array
     */
    private function decodeActionItemTargets( array $inventories, ItemTargetDefinition $definition ) {
        $targets = [];

        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:
                foreach ($inventories as &$inv)
                    foreach ($inv->getItems() as &$item)
                        if ($this->action_handler->targetDefinitionApplies($item,$definition))
                            $targets[] = [ $item->getId(), $this->translator->trans( $item->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemTypeSelectionType:
                if ($definition->getTag())
                    foreach ($this->inventory_handler->resolveItemProperties($definition->getTag()) as &$prop)
                        /** @var $prop ItemPrototype */
                        $targets[] = [ $prop->getId(), $this->translator->trans( $prop->getLabel(), [], 'items' ), "build/images/item/item_{$prop->getIcon()}.gif" ];

                if ($definition->getPrototype())
                    $targets[] = [ $definition->getPrototype()->getId(), $this->translator->trans( $definition->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$definition->getPrototype()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemHeroicRescueType:

                foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen->getZone() && round( sqrt(pow($citizen->getZone()->getX(),2 ) + pow($citizen->getZone()->getY(),2 )) ) <= 2)
                        $targets[] = [ $citizen->getId(), $citizen->getUser()->getName(), "build/images/professions/{$citizen->getProfession()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemCitizenType:

                foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen != $this->getActiveCitizen())
                        $targets[] = [ $citizen->getId(), $citizen->getUser()->getName(), "build/images/professions/{$citizen->getProfession()->getIcon()}.gif" ];

                break;
        }

        return $targets;
    }

    protected function getHeroicActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $this->action_handler->getAvailableIHeroicActions( $this->getActiveCitizen(),  $available, $crossed );
        if (empty($available) && empty($crossed)) return [];

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'action' => $a->getAction(), 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget() ) : null, 'target_mode' => $a->getAction()->getTarget() ? $a->getAction()->getTarget()->getSpawner() : 0, 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'action' => $c->getAction(), 'targets' => null, 'target_mode' => 0, 'crossed' => true ];

        return $ret;
    }

    protected function getSpecialActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $this->action_handler->getAvailableISpecialActions( $this->getActiveCitizen(),  $available, $crossed );
        if (empty($available) && empty($crossed)) return [];

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'icon' => $a->getIcon(), 'action' => $a->getAction(), 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget() ) : null, 'target_mode' => $a->getAction()->getTarget() ? $a->getAction()->getTarget()->getSpawner() : 0, 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'icon' => $a->getIcon(), 'action' => $c->getAction(), 'targets' => null, 'target_mode' => 0, 'crossed' => true ];
        return $ret;
        
    }

    protected function getCampingActions(): array {
      $ret = [];
      if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false)) return $ret;

      $this->action_handler->getAvailableCampingActions( $this->getActiveCitizen(), $available, $crossed );

      foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'item' => null, 'action' => $a, 'targets' => null, 'crossed' => false ];
      foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'item' => null, 'action' => $c, 'targets' => null, 'crossed' => true ];

      return $ret;
    }

    protected function getHomeActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getHome()->getChest()];
        $this->action_handler->getAvailableHomeActions( $this->getActiveCitizen(), $available, $crossed );

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'icon' => $a->getIcon(), 'item' => null, 'action' => $a->getAction(), 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget() ) : null, 'target_mode' => $a->getAction()->getTarget() ? $a->getAction()->getTarget()->getSpawner() : 0, 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'icon' => $c->getIcon(), 'item' => null, 'action' => $c->getAction(), 'targets' => null, 'target_mode' => 0, 'crossed' => true ];

        return $ret;
    }

    protected function getItemActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $items = [];
        foreach ($this->getActiveCitizen()->getInventory()->getItems() as $item) $items[] = $item;
        if ($this->getActiveCitizen()->getZone() === null) foreach ($this->getActiveCitizen()->getHome()->getChest()->getItems() as $item) $items[] = $item;

        foreach ($items as $item) if (!$item->getBroken()) {

            $this->action_handler->getAvailableItemActions( $this->getActiveCitizen(), $item, $available, $crossed );
            if (empty($available) && empty($crossed)) continue;

            foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'item' => $item, 'action' => $a, 'targets' => $a->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getTarget() ) : null, 'target_mode' => $a->getTarget() ? $a->getTarget()->getSpawner() : 0, 'crossed' => false ];
            foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'item' => $item, 'action' => $c, 'targets' => null, 'target_mode' => 0, 'crossed' => true ];
        }

        return $ret;
    }

    protected function getItemCombinations(bool $inside): array {
        $town = $this->getActiveCitizen()->getTown();
        $source_inv = $this->getActiveCitizen()->getZone() ? [ $this->getActiveCitizen()->getInventory() ] : [ $this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getHome()->getChest() ];

        if ($this->getActiveCitizen()->getZone() && $this->getTownConf()->get( TownConf::CONF_MODIFIER_FLOOR_ASMBLY, false )) $source_inv[] = $this->getActiveCitizen()->getZone()->getFloor();

        $recipes = $this->entity_manager->getRepository(Recipe::class)->findByType( [Recipe::ManualAnywhere, $inside ? Recipe::ManualInside : Recipe::ManualOutside] );
        $out = [];
        $source_db = [];
        foreach ($recipes as $recipe) {
            /** @var Recipe $recipe */
            $found_provoking = false;
            foreach ($recipe->getProvoking() as $proto)
                if ($this->inventory_handler->countSpecificItems( $source_inv, $proto )) {
                    $found_provoking = true;
                    break;
                }

            if (!$found_provoking) continue;
            $out[] = $recipe;

            if ($recipe->getSource())
                foreach ($recipe->getSource()->getEntries() as $entry)
                    /** @var ItemGroupEntry $entry */
                    if (!isset( $source_db[ $entry->getPrototype()->getId() ] ))
                        $source_db[ $entry->getPrototype()->getId() ] = $this->inventory_handler->countSpecificItems( $source_inv, $entry->getPrototype() );
        }

        return [ 'recipes' => $out, 'source_items' => $source_db ];
    }

    protected function renderInventoryAsBank( Inventory $inventory ) {
        $qb = $this->entity_manager->createQueryBuilder();
        $qb
            ->select('i.id', 'c.label as l1', 'cr.label as l2', 'SUM(i.count) as n')->from('App:Item','i')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory);
        if ($this->getTownConf()->get(TownConf::CONF_MODIFIER_POISON_STACK, false))
            $qb->groupBy('i.prototype', 'i.broken');
        else $qb->groupBy('i.prototype', 'i.broken', 'i.poison');
        $qb
            ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
            ->leftJoin('App:ItemCategory', 'c', Join::WITH, 'p.category = c.id')
            ->leftJoin('App:ItemCategory', 'cr', Join::WITH, 'c.parent = cr.id')
            ->addOrderBy('c.ordering','ASC')
            ->addOrderBy('p.id', 'ASC')
            ->addOrderBy('i.id', 'ASC');

        $data = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $final = [];
        $cache = [];

        foreach ($data as $entry) {
            $label = $entry['l2'] ?? $entry['l1'] ?? 'Sonstiges';
            if (!isset($final[$label])) $final[$label] = [];
            $final[$label][] = [ $entry['id'], $entry['n'] ];
            $cache[] = $entry['id'];
        }

        $item_list = $this->entity_manager->getRepository(Item::class)->findAllByIds($cache);
        foreach ( $final as $label => &$entries )
            $entries = array_map(function( array $entry ) use (&$item_list): BankItem { return new BankItem( $item_list[$entry[0]], $entry[1] ); }, $entries);

        return $final;
    }

    public function generic_devour_api(Citizen $aggressor, Citizen $victim) {
        if ($aggressor->getId() === $victim->getId())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($aggressor->getZone()) {
            if (!$victim->getZone() || $victim->getZone()->getId() !== $aggressor->getZone()->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        } else {
            if ($victim->getZone())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $notes = [];

        if ($victim->getAlive()) {

            if ($this->citizen_handler->hasStatusEffect($aggressor, 'tg_ghoul_eat'))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if ($victim->hasRole('ghoul')) {
                $this->addFlash('notice', $this->translator->trans('Du kannst diesen Bürger nicht angreifen... er riecht nicht wie die anderen. Moment... Dieser Bürger ist ein Ghul, genau wie du!', [], 'game'));
                return AjaxResponse::success();
            }

            $notes[] = $this->translator->trans( 'Mit weit aufgerissenem Maul stürzt du dich auf %citizen%. Unter der Wucht deiner brutalen Schläge und Tritte sackt er ziemlich schnell zusammen.', ['%citizen%' => $victim->getUser()->getName()], 'game' );
            $notes[] = $this->translator->trans( 'Mit ein paar unschönen Tritten gegen seinen Kopf vergewisserst du dich, dass er garantiert nicht mehr aufstehen wird. Na los! Bring deinen Job zuende und verspeise ihn!', [], 'game' );

            $give_ap = 6;

            if ($aggressor->getZone()) {
                $this->entity_manager->persist($this->log->citizenBeyondGhoulAttack($aggressor, $victim, true));
                $this->entity_manager->persist($this->log->citizenBeyondGhoulAttack($aggressor, $victim, false));
            } else {
                $give_ap = 7;

                $cc = 0;
                foreach ($aggressor->getTown()->getCitizens() as $c)
                    if ($c->getAlive() && !$c->getZone() && $c->getId() !== $aggressor->getId() && $c->getId() !== $victim->getId()) $cc++;
                $cc = (float)$cc / (float)$aggressor->getTown()->getPopulation(); // Completely arbitrary

                if ($this->random_generator->chance($cc)) {

                    $this->entity_manager->persist($this->log->citizenTownGhoulAttack($aggressor,$victim));
                    $notes[] = $this->translator->trans( 'Gut gemacht!', [], 'game' );

                } else $notes[] = $this->translator->trans( 'Du wurdest beobachtet! Die anderen Bürger wurden gewarnt!', [], 'game' );
            }

            if ($give_ap > $aggressor->getAp())
                $this->citizen_handler->setAP($aggressor, false, $give_ap, null );

            $aggressor->setGhulHunger( max(0, $aggressor->getGhulHunger() - 65) );
            $this->picto_handler->give_picto($aggressor, 'r_cannib_#00');

            $stat_down = false;
            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'drugged') && $this->citizen_handler->hasStatusEffect($victim, 'drugged')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'drugged' );
            }

            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'addict') && $this->citizen_handler->hasStatusEffect($victim, 'addict')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'addict' );
            }

            if (!$this->citizen_handler->hasStatusEffect($aggressor, 'drunk') && $this->citizen_handler->hasStatusEffect($victim, 'drunk')) {
                $stat_down = true;
                $this->citizen_handler->inflictStatus( $aggressor, 'drunk' );
                $this->citizen_handler->inflictStatus( $aggressor, 'tg_no_hangover' );
            }

            if ($stat_down)
                $notes[] = $this->translator->trans( 'Einige gesundheitliche Askekte deines Opfers sind auf dich übergegangen ...', [], 'game' );

            $this->citizen_handler->inflictStatus( $aggressor, 'tg_ghoul_eat' );

            $this->death_handler->kill($victim, CauseOfDeath::GhulEaten);
            $this->entity_manager->persist($this->log->citizenDeath( $victim ) );

        } else {

            if ($this->citizen_handler->hasStatusEffect($aggressor, 'tg_ghoul_corpse'))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if ($aggressor->getZone() || !$victim->getHome()->getHoldsBody())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            $this->entity_manager->persist( $this->log->citizenDisposal($aggressor, $victim, 4) );

            $aggressor->setGhulHunger( max(0, $aggressor->getGhulHunger() - 10) );
            $victim->getHome()->setHoldsBody(false);
            $this->picto_handler->give_picto($aggressor, 'r_cannib_#00');

            $notes[] = $this->translator->trans('Nicht so appetitlich wie frisches Menschenfleisch, aber es stillt nichtsdestotrotz deinen Hunger... zumindest ein bisschen. Wenigstens war das Fleisch noch halbwegs zart.', [], 'game');
            $this->citizen_handler->inflictStatus( $aggressor, 'tg_ghoul_corpse' );
        }

        if ($notes)
            $this->addFlash('note', implode('<hr />', $notes));

        $this->entity_manager->persist($aggressor);
        $this->entity_manager->persist($victim);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()]  );
        }

        return AjaxResponse::success();
    }

    public function generic_attack_api(Citizen $aggressor, Citizen $defender): Response {
        if ($aggressor->getId() === $defender->getId() || !$defender->getAlive())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($aggressor->getZone()) {
            if (!$defender->getZone() || $defender->getZone()->getId() !== $aggressor->getZone()->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        } else {
            if ($defender->getZone())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $ap = $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_AP, 4);
        if ($this->citizen_handler->isTired($aggressor) || $aggressor->getAp() < $ap)
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $attack_protect = $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_PROTECT, false) ||
            ($aggressor->getUser()->getAllSoulPoints() < $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_SP, 20));
        if ($attack_protect) {
            foreach ($aggressor->getTown()->getCitizens() as $c)
                if ($c->getAlive() && $c->hasRole('ghoul'))
                    $attack_protect = false;
        }

        if ($defender->hasRole('ghoul')) {

            $this->citizen_handler->setAP($aggressor, true, -5);
            $this->addFlash('notice',
                $this->translator->trans('Mit aller Gewalt greifst du %citizen% an! Du hast den Überraschungsmoment auf deiner Seite und am Ende trägt %citizen% eine schwere Verletzung davon.', ['%citizen%' => $defender->getUser()->getName()], 'game')
                . "<hr />" .
                $this->translator->trans('Plötzlich sackt %citizen% in sich zusammen, seine Augen drehen sich nach hinten, und mit einem schauerhaften Gurgeln löst sich sein ganzer Körper vor deinen Augen auf und hinterlässt nur den üblen Geruch von Tod und Verwesung! Es gibt keinen Zweifel mehr: %citizen% war ein Ghul!!', ['%citizen%' => $defender->getUser()->getName()], 'game')
            );
            $this->entity_manager->persist($this->log->citizenAttack($aggressor, $defender, true));
            $this->death_handler->kill($defender, CauseOfDeath::GhulBeaten);
            $this->entity_manager->persist($this->log->citizenDeath( $defender ) );

        } elseif ($attack_protect) {

            $this->addFlash('error', $this->translator->trans('Bleib mal ganz geschmeidig! In dieser Stadt gibt es keine Ghule, also solltest du auch nicht herumlaufen und grundlos Leute verprügeln. M\'Kay?', [], 'game'));

        } elseif ( $this->citizen_handler->isWounded( $defender ) ) {

            $this->addFlash('error', $this->translator->trans('%citizen% ist bereits verletzt; ihn erneut anzugreifen wird dir nichts bringen.', ['%citizen%' => $defender->getUser()->getName()], 'game'));

        } else {

            $this->citizen_handler->setAP( $aggressor, true, -5 );
            $wound = $this->random_generator->chance( $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_CHANCE, 0.5) );
            $this->entity_manager->persist($this->log->citizenAttack($aggressor, $defender, $wound));
            if ($wound) {
                $this->addFlash('notice',
                    $this->translator->trans('Mit aller Gewalt greifst du %citizen% an! Du hast den Überraschungsmoment auf deiner Seite und am Ende trägt %citizen% eine schwere Verletzung davon.', ['%citizen%' => $defender->getUser()->getName()], 'game')
                );
                $this->citizen_handler->inflictWound($defender);

            } else $this->addFlash('notice',
                $this->translator->trans('Mit aller Gewalt greifst du %citizen% an! Ihr tauscht für eine Weile Schläge aus, bis ihr euch schließlich größtenteils unverletzt voneinander trennt.', ['%citizen%' => $defender->getUser()->getName()], 'game')
            );
        }

        $this->entity_manager->persist($aggressor);
        $this->entity_manager->persist($defender);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success( );
    }

    public function generic_item_api(Inventory &$up_target, Inventory &$down_target, bool $allow_down_all, JSONRequestParser $parser, InventoryHandler $handler, Citizen $citizen = null, $hide = false): Response {
        $item_id = (int)$parser->get('item', -1);
        $direction = $parser->get('direction', '');
        $allowed_directions = ['up','down'];
        if ($allow_down_all) $allowed_directions[] = 'down-all';
        $item = $item_id < 0 ? null : $this->entity_manager->getRepository(Item::class)->find( $item_id );

        $carrier_items = ['bag_#00','bagxl_#00','cart_#00','pocket_belt_#00'];

        $drop_carriers = false;
        if ($direction === 'down' && $allow_down_all && in_array($item->getPrototype()->getName(), $carrier_items)) {
            $direction = 'down-all';
            $drop_carriers = true;
        }

        if (in_array($direction, $allowed_directions)) {
            if($citizen === null)
                $citizen = $this->getActiveCitizen();

            $inv_source = $direction === 'up' ? $down_target : $up_target;
            $inv_target = $direction !== 'up' ? $down_target : $up_target;

            $items = [];
            if ($direction !== 'down-all') {
                $item = $this->entity_manager->getRepository(Item::class)->find( $item_id );
                if ($item && $item->getInventory()) $items = [$item];
            } else{
                $items = $drop_carriers ? $citizen->getInventory()->getItems() : array_filter($citizen->getInventory()->getItems()->getValues(), function(Item $i) use ($carrier_items) {
                    return !in_array($i->getPrototype()->getName(), $carrier_items) && !$i->getEssential();
                });
            }

            $bank_up = null;
            if ($inv_source->getTown()) $bank_up = true;
            if ($inv_target->getTown()) $bank_up = false;

            $floor_up = null;
            if ($inv_source->getZone()) $floor_up = true;
            if ($inv_target->getZone()) $floor_up = false;

            $steal_up = null;
            if ($inv_source->getHome() && $inv_source->getHome()->getId() !== $citizen->getHome()->getId()) $steal_up = true;
            if ($inv_target->getHome() && $inv_target->getHome()->getId() !== $citizen->getHome()->getId()) $steal_up = false;

            $errors = [];
            $item_count = count($items);
            $dead = false;

            $target_citizen = $inv_target->getCitizen() ?? $inv_source->getCitizen() ?? $citizen;

            foreach ($items as $current_item){
                if($current_item->getPrototype()->getName() == 'soul_red_#00' && $floor_up) {
                    // We pick a read soul in the World Beyond
                    if($target_citizen && !$this->citizen_handler->hasStatusEffect($target_citizen, "tg_shaman_immune")) {
                        $dead = true;
                        // He is not immune, he dies.
                        $rem = [];
                        $this->death_handler->kill( $target_citizen, CauseOfDeath::Haunted, $rem );
                        $this->entity_manager->persist( $this->log->citizenDeath( $target_citizen ) );

                        // The red soul vanishes too
                        $this->inventory_handler->forceRemoveItem($current_item);
                    }
                }

                if(!$dead){
                    if (($error = $handler->transferItem(
                            $citizen,
                            $current_item, $inv_source, $inv_target, InventoryHandler::ModalityNone, $this->getTownConf()->get(TownConf::CONF_MODIFIER_CARRY_EXTRA_BAG, false)
                        )) === InventoryHandler::ErrorNone) {

                        if ($bank_up !== null)  $this->entity_manager->persist( $this->log->bankItemLog( $target_citizen, $current_item->getPrototype(), !$bank_up, $current_item->getBroken() ) );
                        if ($floor_up !== null) {
                            if($floor_up && $current_item->getPrototype()->getName() == 'soul_blue_#00' && $current_item->getFirstPick()) {
                                $current_item->setFirstPick(false);
                                // In the "Job" version of the shaman, the one that pick a blue soul for the 1st time gets the "r_collec" picto
                                if ($this->getTownConf()->get(TownConf::CONF_FEATURE_SHAMAN_MODE, "normal") == "job")
                                    $this->picto_handler->give_picto($target_citizen, "r_collec2_#00");
                                $this->entity_manager->persist($current_item);
                            }
                            if (!$hide && !$current_item->getHidden()) $this->entity_manager->persist( $this->log->beyondItemLog( $target_citizen, $current_item->getPrototype(), !$floor_up, $current_item->getBroken() ) );
                        }

                        if ($steal_up !== null) {

                            $this->citizen_handler->inflictStatus($target_citizen, 'tg_steal');
                            $victim_home = $steal_up ? $inv_source->getHome() : $inv_target->getHome();

                            // Give picto steal
                            $pictoName = "r_theft_#00";
                            if(!$victim_home->getCitizen()->getAlive())
                                $pictoName = "r_plundr_#00";

                            $isSanta = false;
                            $isLeprechaun = false;
                            $hasExplodingDoormat = false;

                            if ($this->inventory_handler->countSpecificItems($citizen->getInventory(), "christmas_suit_full_#00") > 0){
                                $pictoName = "r_santac_#00";
                                $isSanta = true;
                            }

                            if ($this->inventory_handler->countSpecificItems($citizen->getInventory(), "leprechaun_suit_#00") > 0){
                                $pictoName = "r_lepre_#00";
                                $isLeprechaun = true;
                            }

                            if ($this->inventory_handler->countSpecificItems($victim_home->getChest(), "trapma_#00") > 0)
                                $hasExplodingDoormat = true;

                            $this->picto_handler->give_picto($citizen, $pictoName);

                            if($steal_up) {
                                if ($hasExplodingDoormat && $victim_home->getCitizen()->getAlive()) {

                                    if ($this->citizen_handler->isWounded($citizen))
                                        $this->death_handler->kill($citizen, CauseOfDeath::ExplosiveDoormat);
                                    else {
                                        $this->citizen_handler->inflictWound( $citizen );
                                        $dm = $this->inventory_handler->fetchSpecificItems($victim_home->getChest(), [new ItemRequest('trapma_#00')]);
                                        if (!empty($dm)) $this->inventory_handler->forceRemoveItem(array_pop($dm));
                                    }
    
                                    $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                                    $this->addFlash( 'notice', $this->translator->trans('"Einen Schritt weiter..." stand auf %victim%s Fußmatte. Ihre Explosion hat einen bleibenden Eindruck bei dir hinterlassen. Wenn du noch laufen kannst, such dir besser einen Arzt.', 
                                    ['%victim%' => $victim_home->getCitizen()->getUser()->getName()], 'game') );
                                } elseif ($isSanta || $isLeprechaun) {
                                    $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), null, $current_item->getPrototype(), $steal_up, $isSanta, $current_item->getBroken(), $isLeprechaun ) );
                                    $this->addFlash( 'notice', $this->translator->trans('Dank deines Kostüms konntest du %item% von %victim% stehlen, ohne erkannt zu werden', [
                                        '%victim%' => $victim_home->getCitizen()->getUser()->getName(),
                                        '%item%' => "<span>" . $this->translator->trans($current_item->getPrototype()->getLabel(),[], 'items') . "</span>"], 'game') );
                                } elseif ($this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
                                    $victim_home,
                                    $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( 'alarm' ) ) && $victim_home->getCitizen()->getAlive())
                                {
                                    $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                                    $this->citizen_handler->inflictStatus( $citizen, 'terror' );
                                    $this->addFlash( 'notice', $this->translator->trans('%victim%s Alarmanlage hat die halbe Stadt aufgeweckt und dich zu Tode erschreckt!', ['%victim%' => $victim_home->getCitizen()->getUser()->getName()], 'game') );
                                } elseif (($victim_home->getCitizen()->getAlive() && $this->random_generator->chance(0.5)) || !$victim_home->getCitizen()->getAlive()) {
                                    if($victim_home->getCitizen()->getAlive()){
                                        $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                                        $this->addFlash( 'notice', $this->translator->trans('Mist, dein Einbruch bei %victim% ist aufgeflogen...', ['%victim%' => $victim_home->getCitizen()->getUser()->getName()], 'game') );
                                    } else {
                                        $this->entity_manager->persist( $this->log->townLoot( $victim_home->getCitizen(), $citizen, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                                    }
                                } else {
                                    $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), null, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                                    $this->addFlash( 'notice', $this->translator->trans('Sehr gut, niemand hat dich bei deinem Einbruch bei %victim% beobachtet.', ['%victim%' => $victim_home->getCitizen()->getUser()->getName()], 'game') );
                                }
    
                                $this->crow->postAsPM( $victim_home->getCitizen(), '', '', PrivateMessage::TEMPLATE_CROW_THEFT, $current_item->getPrototype()->getId() );
                            } else if($this->random_generator->chance(0.1)) {
                                $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item->getPrototype(), $steal_up, false, $current_item->getBroken() ) );
                            }
                        }
                        if(!$floor_up && $hide) {
                            $current_item->setHidden(true);
                        } else {
                            $current_item->setHidden(false);
                        }
                        if ($current_item->getInventory())
                            $this->entity_manager->persist($current_item);
                        else $this->entity_manager->remove($current_item);

                    } else $errors[] = $error;
                }
            }

            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

            if (count($errors) < $item_count) {
                return AjaxResponse::success();
            } else if (count($errors) > 0)
                return AjaxResponse::error($errors[0]);
        }
        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    public function generic_recipe_api(JSONRequestParser $parser, ActionHandler $handler, ?callable $trigger_after = null): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Recipe $recipe */
        $recipe = $this->entity_manager->getRepository(Recipe::class)->find( $id );
        if ($recipe === null || !in_array($recipe->getType(), [Recipe::ManualAnywhere, Recipe::ManualOutside, Recipe::ManualInside]))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (($error = $handler->execute_recipe( $citizen, $recipe, $remove, $message )) !== ActionHandler::ErrorNone )
            return AjaxResponse::error( $error );
        else try {

            if ($trigger_after) $trigger_after($recipe);

            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            foreach ($remove as $e) $this->entity_manager->remove( $e );
            $this->entity_manager->flush();
            if ($message) $this->addFlash( 'notice', $message );
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }

    public function get_map_blob(): array {
        $zones = []; $range_x = [PHP_INT_MAX,PHP_INT_MIN]; $range_y = [PHP_INT_MAX,PHP_INT_MIN];
        $zones_classes = [];

        $citizen_is_shaman =
            ($this->citizen_handler->hasRole($this->getActiveCitizen(), 'shaman')
                || $this->getActiveCitizen()->getProfession()->getName() == 'shaman');

        $soul_zones_ids = $citizen_is_shaman
            ? array_map(function(Zone $z) { return $z->getId(); },$this->zone_handler->getSoulZones( $this->getActiveCitizen()->getTown() ) )
            : [];

        foreach ($this->getActiveCitizen()->getTown()->getZones() as $zone) {
            $x = $zone->getX();
            $y = $zone->getY();

            $range_x = [ min($range_x[0], $x), max($range_x[1], $x) ];
            $range_y = [ min($range_y[0], $y), max($range_y[1], $y) ];

            if (!isset($zones[$x])) $zones[$x] = [];
            $zones[$x][$y] = $zone;

            if (!isset($zones_attributes[$x])) $zones_attributes[$x] = [];
            $zones_classes[$x][$y] = $this->zone_handler->getZoneClasses(
                $this->getActiveCitizen()->getTown(),
                $zone,
                $this->getActiveCitizen(),
                in_array($zone->getId(), $soul_zones_ids)
            );
        }

        return [
            'map_data' => [
                'zones' =>  $zones,
                'zones_classes' =>  $zones_classes,
                'town_devast' => $this->getActiveCitizen()->getTown()->getDevastated(),
                'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown( $this->getActiveCitizen()->getTown() ),
                'pos_x'  => $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getX() : 0,
                'pos_y'  => $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getY() : 0,
                'map_x0' => $range_x[0],
                'map_x1' => $range_x[1],
                'map_y0' => $range_y[0],
                'map_y1' => $range_y[1],
            ]
        ];
    }

    /**
     * @param int $id
     * @param ItemTargetDefinition|null $target
     * @param Inventory[] $inventories
     * @param object|null $return
     * @return bool
     */
    private function extract_target_object(int $id, ?ItemTargetDefinition $target, array $inventories, ?object &$return): bool {
        $return = null;
        if (!$target) return true;

        switch ($target->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:
                $return = $this->entity_manager->getRepository(Item::class)->find( $id );
                if (!$return) return false;

                foreach ($inventories as $inventory)
                    if ($inventory->getItems()->contains( $return ))
                        return true;

                return false;
            case ItemTargetDefinition::ItemTypeSelectionType:
                $return = $this->entity_manager->getRepository(ItemPrototype::class)->find( $id );
                if (!$return) return false;
                return true;
            case ItemTargetDefinition::ItemHeroicRescueType:
                $return = $this->entity_manager->getRepository(Citizen::class)->find( $id );
                if ($return->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId()) {
                    $return = null;
                    return false;
                }
                return true;
                break;
            case ItemTargetDefinition::ItemCitizenType:
                $return = $this->entity_manager->getRepository(Citizen::class)->find( $id );
                if (!$return->getAlive() || $return->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId()) {
                    $return = null;
                    return false;
                }
                return true;
                break;

            default: return false;
        }
    }

    public function generic_heroic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var HeroicActionPrototype|null $heroic */
        $heroic = ($action_id < 0) ? null : $this->entity_manager->getRepository(HeroicActionPrototype::class)->find( $action_id );

        if ( !$heroic ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if (!$citizen->getProfession()->getHeroic() || !$citizen->getHeroicActions()->contains( $heroic )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $heroic->getAction()->getTarget(), [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $heroic->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            $heroic_action = $heroic->getAction();
            if ($trigger_after) $trigger_after($heroic_action);
            $citizen->removeHeroicAction($heroic);

            // Add the picto Heroic Action
            $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_heroac_#00");
            $this->picto_handler->give_picto($citizen, $picto);

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_special_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var SpecialActionPrototype|null $heroic */
        $special = ($action_id < 0) ? null : $this->entity_manager->getRepository(SpecialActionPrototype::class)->find( $action_id );

        if ( !$special ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if (!$citizen->getSpecialActions()->contains( $special )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $special->getAction()->getTarget(), [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $special->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            $special_action = $special->getAction();
            if ($trigger_after) $trigger_after($special_action);
            $citizen->removeSpecialAction($special);

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_home_action_api(JSONRequestParser $parser): Response {
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var HomeActionPrototype|null $home_action */
        $home_action = ($action_id < 0) ? null : $this->entity_manager->getRepository(HomeActionPrototype::class)->find( $action_id );

        if ( !$home_action ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        if ($citizen->getZone()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $home_action->getAction()->getTarget(), [ $citizen->getInventory(), $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $home_action->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {
            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_camping_action_api(JSONRequestParser $parser): Response {
      if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false))
          return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

      $action_id = (int)$parser->get('action', -1);

      /** @var Item|ItemPrototype|null $target */
      $target = null;
      /** @var CampingActionPrototype|null $heroic */
      $camping = ($action_id < 0) ? null : $this->entity_manager->getRepository(CampingActionPrototype::class)->find( $action_id );

      if ( !$camping ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
      $citizen = $this->getActiveCitizen();

      $zone = $citizen->getZone();
      if ($zone && $zone->getX() === 0 && $zone->getY() === 0 ) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

      $item = null;
      if (($error = $this->action_handler->execute( $citizen, $item, $target, $camping->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

        switch($camping->getName()){
            case 'campsite_improve':
                $this->entity_manager->persist($this->log->beyondCampingImprovement($citizen));
                break;
            case 'campsite_hide':
            case 'campsite_tomb':
                $this->entity_manager->persist($this->log->beyondCampingHide($citizen));
                break;
            case "campsite_unhide":
            case "campsite_untomb":
                $this->entity_manager->persist($this->log->beyondCampingUnhide($citizen));
                break;
        }

        $this->entity_manager->persist($citizen);
        foreach ($remove as $remove_entry)
            $this->entity_manager->remove($remove_entry);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
        }

        if ($msg) $this->addFlash( 'notice', $msg );
      } elseif ($error === ActionHandler::ErrorActionForbidden) {
        if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
        return AjaxResponse::error($error, ['message' => $msg]);
      }
      else return AjaxResponse::error( $error );

      return AjaxResponse::success();
    }

    public function generic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null, ?Citizen $base_citizen = null): Response {
        $item_id =   (int)$parser->get('item',   -1);
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|null $item */
        $item   = ($item_id < 0)   ? null : $this->entity_manager->getRepository(Item::class)->find( $item_id );
        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var ItemAction|null $action */
        $action = ($action_id < 0) ? null : $this->entity_manager->getRepository(ItemAction::class)->find( $action_id );

        if ( !$item || !$action || $item->getBroken() ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $base_citizen ?? $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if ($zone && !$this->zone_handler->check_cp($zone) && !$action->getAllowWhenTerrorized() && $this->citizen_handler->hasStatusEffect($citizen, 'terror') && !$this->zone_handler->check_cp($this->getActiveCitizen()->getZone()))
            return AjaxResponse::error( BeyondController::ErrorTerrorized );

        $secondary_inv = $zone ? $zone->getFloor() : $citizen->getHome()->getChest();
        if (!$citizen->getInventory()->getItems()->contains( $item ) && !$secondary_inv->getItems()->contains( $item )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if (!$this->extract_target_object( $target_id, $action->getTarget(), [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $url = null;

        if (($error = $this->action_handler->execute( $citizen, $item, $target, $action, $msg, $remove )) === ActionHandler::ErrorNone) {

            if ($trigger_after) $trigger_after($action);

            if ($action->getName() == 'improve') {
                $this->entity_manager->persist($this->log->beyondCampingItemImprovement($citizen, $item->getPrototype()));
            }

            $this->entity_manager->persist($citizen);
            if ($item->getInventory())
                $this->entity_manager->persist($item);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
            
            if($text = $this->entity_manager->getRepository(FoundRolePlayText::class)->findNextUnreadText($this->getUser())){
                /** @var FoundRolePlayText $text */
                $url = $this->generateUrl("soul_rp", ['page' => 1, 'id' => $text->getId()]);
            }

        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'items');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );
        return AjaxResponse::success( true, ['url' => $url] );
    }
}
