<?php


namespace App\EventListener\Game\Action;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeIntrusion;
use App\Entity\ItemPrototype;
use App\Enum\ActionHandler\CountType;
use App\Enum\ActionHandler\PointType;
use App\Enum\Game\TransferItemModality;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\Actions\Game\SpanHeroicActionInheritanceTreeAction;
use App\Service\CitizenHandler;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Structures\FriendshipActionTarget;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class HeroicItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            InventoryHandler::class,
            EventProxyService::class,
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            CitizenHandler::class,
            RandomGenerator::class,
            TranslatorInterface::class,
            ZoneHandler::class,
            UserHandler::class,
            PictoHandler::class,

            InvalidateTagsInAllPoolsAction::class,
            SpanHeroicActionInheritanceTreeAction::class,
        ];
    }

    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {
            // Tamer
            case 4:case 5:case 16:case 17: {

                // The tamer does not work if the door is closed
                if (!$event->citizen->getTown()->getDoor()) {
                    $event->cache->addTag('fail');
                    $event->cache->addTag('door-closed');
                    break;
                }

                $heavy = $event->type === 5 || $event->type === 17;

                $source = $event->citizen->getInventory();
                $create_log = ($event->type === 4 || $event->type === 5);
                $bank = ($event->type === 4 || $event->type === 5) ? $event->citizen->getTown()->getBank() : $event->citizen->getHome()->getChest();

                $heavy_break = false;
                $item_count = 0; $success_count = 0;

                foreach ( $event->citizen->getInventory()->getItems() as $target_item ) {
                    if ($target_item->getEssential()) continue;
                    if ($target_item !== $event->item) $item_count++;
                    if ($target_item->getPrototype()->getHeavy())
                        if (!$heavy) $heavy_break = true;
                }

                if ($heavy_break) {
                    $event->cache->addTag('fail');
                    $event->cache->addTag('too-heavy');
                } elseif ($this->getService(InventoryHandler::class)->getFreeSize( $bank ) < $item_count) {
                    $event->cache->addTag('fail');
                    $event->cache->addTag('no-room');
                    $event->cache->addToCounter( CountType::Items, $item_count );
                    $event->cache->addTranslationKey('size', ($freeSize = $this->getService(InventoryHandler::class)->getFreeSize($bank)) > 0 ? $freeSize : 0);
                } else {
                    foreach ( $event->citizen->getInventory()->getItems() as $target_item ) if ($target_item !== $event->item) {
                        if ($this->getService(EventProxyService::class)->transferItem($event->citizen, $target_item, $source, $bank, TransferItemModality::Tamer) === InventoryHandler::ErrorNone) {
                            $success_count++;
                            if ($create_log) $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->bankItemTamerLog($event->citizen, $target_item->getPrototype(), $target_item->getBroken()));
                        }
                    }

                    if ($success_count > 0) {
                        if ($event->item->getPrototype()->getName() === 'tamed_pet_#00' || $event->item->getPrototype()->getName() === 'tamed_pet_drug_#00' )
                            $event->item->setPrototype( $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_off_#00']) );
                        $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->beyondTamerSendLog($event->citizen, $success_count));
                    } else {
                        $event->cache->addTag('no-items');
                        $event->cache->addTag('fail');
                    }
                }

                break;
            }

            // Survivalist
            case 6:case 7: {
                $drink = $event->type === 6;
                $chances = 1;
                if      ($event->citizen->getTown()->getDay() >= 20)  $chances = .50;
                else if ($event->citizen->getTown()->getDay() >= 15)  $chances = .60;
                else if ($event->citizen->getTown()->getDay() >= 13)  $chances = .70;
                else if ($event->citizen->getTown()->getDay() >= 10)  $chances = .80;
                else if ($event->citizen->getTown()->getDay() >= 5)   $chances = .85;

                if( $event->citizen->getTown()->getDevastated() ) $chances = max(0.1, $chances - 0.2);

                $give_ap = false;
                if ($this->getService(RandomGenerator::class)->chance($chances)) {
                    if ($drink) {
                        $event->citizen->setWalkingDistance(0);
                        if($event->citizen->hasRole('ghoul')){
                            $this->getService(CitizenHandler::class)->inflictWound($event->citizen);
                        } else if($this->getService(CitizenHandler::class)->hasStatusEffect($event->citizen, 'thirst2')){
                            $this->getService(CitizenHandler::class)->removeStatus($event->citizen, 'thirst2');
                            $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'thirst1');
                        } else {
                            $this->getService(CitizenHandler::class)->removeStatus($event->citizen, 'thirst1');
                            if (!$this->getService(CitizenHandler::class)->hasStatusEffect($event->citizen, 'hasdrunk')) {
                                $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'hasdrunk');
                                $give_ap = true;
                            }
                        }
                    } else {
                        if (!$this->getService(CitizenHandler::class)->hasStatusEffect($event->citizen, 'haseaten')) {
                            $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'haseaten');
                            $give_ap = true;
                        }
                    }

                    if($give_ap){
                        $old_ap = $event->citizen->getAp();
                        if ($old_ap < 6)
                            $this->getService(CitizenHandler::class)->setAP($event->citizen, false, 6, 0);
                        $event->cache->addPoints( PointType::AP, $event->citizen->getAp() - $old_ap );
                    }

                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->outsideDigSurvivalist( $event->citizen ) );
                    $event->cache->addTranslationKey('casino', $this->getService(TranslatorInterface::class)->trans($drink ? 'Äußerst erfrischend, und sogar mit einer leichten Note von Cholera.' : 'Immer noch besser als das Zeug, was die Köche in der Stadt zubereiten....', [], 'items'));

                } else
                    $event->cache->addTranslationKey('casino', $this->getService(TranslatorInterface::class)->trans('So viel zum Survivalbuch. Kein Wunder, dass dieses Buch nicht über die Grundstufe hinausgekommen ist... Du hast absolut nichts gefunden, aber das wusstest du wahrscheinlich schon.', [], 'items'));

                break;
            }

            // Heroic teleport action
            case 8:case 9: {
                $jumper = null;
                if ($event->type === 8 && $event->citizen->getZone())
                    $jumper = $event->citizen;

                if ($event->type === 9 && is_a( $event->target, Citizen::class ))
                    $jumper = $event->target;

                if (!$jumper) break;
                $zone = $jumper->getZone();
                if (!$zone) break;

                $this->getService(ZoneHandler::class)->updateZone( $zone );
                $cp_ok = $this->getService(ZoneHandler::class)->isZoneUnderControl( $zone );

                if ($dig_timer = $jumper->getCurrentDigTimer()) {
                    $dig_timer->setPassive(true);
                    $this->getService(EntityManagerInterface::class)->persist( $dig_timer );
                }

                foreach ($jumper->getLeadingEscorts() as $escort)
                    $escort->getCitizen()->getEscortSettings()->setLeader(null);

                if ($jumper->getEscortSettings()) {
                    $this->getService(EntityManagerInterface::class)->remove($jumper->getEscortSettings());
                    $jumper->setEscortSettings(null);
                }

                if ($jumper->activeExplorerStats())
                    $jumper->activeExplorerStats()->setActive( false );

                $this->getService(CitizenHandler::class)->removeStatus($jumper, 'tg_hide');
                $this->getService(CitizenHandler::class)->removeStatus($jumper, 'tg_tomb');
                $jumper->setCampingTimestamp(0);
                $jumper->setCampingChance(0);

                $jumper->setZone(null);
                $zone->removeCitizen( $jumper );

                ($this->getService(InvalidateTagsInAllPoolsAction::class))("town_{$jumper->getTown()->getId()}_zones_{$zone->getX()}_{$zone->getY()}");

                foreach ($this->getService(EntityManagerInterface::class)->getRepository(HomeIntrusion::class)->findBy(['victim' => $jumper]) as $homeIntrusion)
                    $this->getService(EntityManagerInterface::class)->remove($homeIntrusion);

                /*if ( $zone->getX() !== 0 || $zone->getY() !== 0 ) {
                    $zero_zone = $this->getService(EntityManagerInterface::class)->getRepository(Zone::class)->findOneByPosition( $zone->getTown(), 0, 0 );

                    if ($others_are_here) $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->outsideMove( $jumper, $zone, $zero_zone, true ) );
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->outsideMove( $jumper, $zero_zone, $zone, false ) );
                }*/
                $others_are_here = $zone->getCitizens()->count() > 0;
                if ( ($event->type === 8) && $others_are_here )
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->heroicReturnLog( $event->citizen, $zone ) );
                if ( $event->type === 9 )
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->heroicRescueLog( $event->citizen, $jumper, $zone ) );
                $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->doorPass( $jumper, true ) );
                $this->getService(ZoneHandler::class)->handleCitizenCountUpdate( $zone, $cp_ok, $jumper );

                break;
            }

            // Friendship
            case 70:
                if (!is_a($event->target, FriendshipActionTarget::class)) break;

                if (!$this->getService(UserHandler::class)->checkFeatureUnlock($event->citizen->getUser(), 'f_share', true))
                    break;

                $event->citizen->getHeroicActions()->removeElement( $event->target->action() );
                $event->citizen->getUsedHeroicActions()->add( $event->target->action() );

                $treeService = $this->getService(SpanHeroicActionInheritanceTreeAction::class);
                $upgrade_actions = ($treeService)( $event->target->action(), 1 );
                $downgrade_actions = ($treeService)( $event->target->action(), -1 );

                $valid = !$this->getService(CitizenHandler::class)->hasStatusEffect( $event->target->citizen(), 'tg_rec_heroic' );

                if ($valid && $event->target->citizen()->getProfession()->getHeroic()) {
                    if ($event->target->citizen()->getHeroicActions()->contains( $event->target->action() ))
                        $valid = false;
                    foreach ( $upgrade_actions as $a ) if ($event->target->citizen()->getHeroicActions()->contains( $a ))
                        $valid = false;
                }

                $event->target->citizen()->getSpecificActionCounter(
                    ActionCounter::ActionTypeReceiveHeroic
                )->increment()->addRecord( [
                                               'action' => $event->target->action()->getName(),
                                               'from' => $event->citizen->getId(),
                                               'valid' => $valid,
                                               'seen' => false
                                           ] );

                if ($valid) {
                    $this->getService(PictoHandler::class)->award_picto_to( $event->citizen, 'r_share_#00' );
                    $this->getService(CitizenHandler::class)->inflictStatus( $event->target->citizen(), 'tg_rec_heroic' );

                    foreach ( $downgrade_actions as $a ) {
                        $event->target->citizen()->getHeroicActions()->removeElement( $a );
                        $event->target->citizen()->getUsedHeroicActions()->removeElement( $a );
                    }
                    foreach ( $upgrade_actions as $a )
                        $event->target->citizen()->getUsedHeroicActions()->removeElement( $a );

                    if ($event->target->citizen()->getProfession()->getHeroic())
                        $event->target->citizen()->getHeroicActions()->add( $event->target->action() );
                    else $event->target->citizen()->addSpecialAction( $event->target->action()->getSpecialActionPrototype() );
                } else $event->cache->addMessage(T::__( 'Du bist aber nicht sicher, ob er damit wirklich etwas anfangen kann...', 'items' ), translationDomain: 'items' );

                break;
        }
    }

}