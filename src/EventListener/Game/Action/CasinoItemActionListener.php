<?php


namespace App\EventListener\Game\Action;

use App\Enum\ActionHandler\PointType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class CasinoItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TranslatorInterface::class,
            CitizenHandler::class,
            InventoryHandler::class,
        ];
    }

    
    
    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        $ap     = false;
        $terror = false;

        switch ($event->type) {
            // Dice
            case 1:
                $trans = $this->getService(TranslatorInterface::class);
                
                $dice = [ mt_rand(1, 6), mt_rand(1, 6), mt_rand(1, 6) ];
                $cmg = $trans->trans('Du hast folgendes gewürfelt: {dc1}, {dc2} und {dc3}.', [
                    '{dc1}' => "<b>{$dice[0]}</b>",
                    '{dc2}' => "<b>{$dice[1]}</b>",
                    '{dc3}' => "<b>{$dice[2]}</b>",
                ], 'items');
                sort($dice);

                if ( $dice[0] === $dice[1] && $dice[0] === $dice[2] ) {
                    $ap = true;
                    $cmg .= ' ' . $trans->trans('Wow, du hast einen Trippel geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                } else if ( $dice[0] === ($dice[1]-1) && $dice[0] === ($dice[2]-2) ) {
                    $ap = true;
                    $cmg .= ' ' . $trans->trans('Wow, du hast eine Straße geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                } else if ( $dice[0] === 1 && $dice[1] === 2 && $dice[2] === 4 ) {
                    $ap = true;
                    $cmg .= ' ' . $trans->trans('Wow, du hast beim ersten Versuch eine 4-2-1 geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                } else if ( $dice[0] === $dice[1] || $dice[1] === $dice[2] )
                    $cmg .= ' ' . $trans->trans('Nicht schlecht, du hast einen Pasch geworfen.', [], 'items');
                else $cmg .= ' ' . $trans->trans('Was für ein Spaß!', [], 'items');

                $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'tg_dice');
                $event->cache->addTranslationKey('casino', $cmg);
                break;
            // Cards
            case 2:
                $trans = $this->getService(TranslatorInterface::class);
                
                $card = mt_rand(0, 53);
                $color = (int)floor($card / 13);
                $value = $card - ( $color * 13 );

                if ( $color > 3 ) {
                    if ($value === 0) {
                        $terror = true;
                        $cmg = $trans->trans('Du ziehst eine Karte... und stellst fest, dass dein Name darauf mit Blut geschrieben steht! Du erstarrst vor Schreck!', [], 'items');
                    } else {
                        $ap = true;
                        $cmg = $trans->trans('Du ziehst eine Karte... und stellst fest, dass du die Karte mit den Spielregeln gezogen hast! Das erheitert dich so sehr, dass du 1AP gewinnst.', [], 'items');
                    }
                } else {
                    $s_color = $trans->trans((['Kreuz','Pik','Herz','Karo'])[$color], [], 'items');
                    $s_value = $value < 9 ? ('' . ($value+2)) : $trans->trans((['Bube','Dame','König','Ass'])[$value-9], [], 'items');

                    $cmg = $trans->trans('Du ziehst eine Karte... es ist: {color} {value}.', [
                        '{color}' => "<strong>{$s_color}</strong>",
                        '{value}' => "<strong>{$s_value}</strong>",
                    ], 'items');

                    if ( $value === 12 ) {
                        $ap = true;
                        $cmg .= '<hr />' . $trans->trans('Das muss ein Zeichen sein! In dieser Welt ist kein Platz für Moral... du erhälst 1AP.', [], 'items');
                    } else if ($value === 10 && $color === 2) {
                        $ap = true;
                        $cmg .= '<hr />' . $trans->trans('Das Symbol der Liebe... dein Herz schmilzt dahin und du erhälst 1AP.', [], 'items');
                    }
                }

                $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'tg_cards');
                $event->cache->addTranslationKey('casino', $cmg);
                break;
            // Guitar
            case 3:
                $trans = $this->getService(TranslatorInterface::class);
                
                $count = 0;
                foreach ($event->citizen->getTown()->getCitizens() as $target_citizen) {
                    // Don't give AP to dead citizen 
                    if(!$target_citizen->getAlive())
                        continue;

                    $this->getService(CitizenHandler::class)->inflictStatus( $target_citizen, 'tg_guitar' );

                    if ($target_citizen->getZone())
                        continue;

                    // Don't give AP if already full
                    if($target_citizen->getAp() >= $this->getService(CitizenHandler::class)->getMaxAP($target_citizen)) {
                        continue;
                    } else {
                        $count += $this->getService(CitizenHandler::class)->setAP($target_citizen,
                                                                true,
                                                              $target_citizen->hasAnyStatus('drunk', 'drugged', 'addict') ? 2 : 1,

                                                                0);
                    }
                }
                $event->cache->addTranslationKey( 'casino', $trans->trans('Mit deiner Gitarre hast du die Stadt gerockt! Die Bürger haben {ap} AP erhalten.', ['{ap}' => $count], 'items') );
                break;
        }

        if ($ap) {
            $this->getService(CitizenHandler::class)->setAP( $event->citizen, true, 1, 1 );
            $event->cache->addPoints( PointType::AP, 1 );
        }

        $prevent_terror = $terror && $this->getService(InventoryHandler::class)->countSpecificItems([$event->citizen->getInventory(), $event->citizen->getHome()->getChest()], 'prevent_terror') > 0;

        if ($terror && !$prevent_terror)
            $this->getService(CitizenHandler::class)->inflictStatus( $event->citizen, 'terror' );
    }

}