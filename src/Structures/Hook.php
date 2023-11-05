<?php


namespace App\Structures;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\Town;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\TownHandler;
use App\Translation\T;

class Hook
{
    /**
     * Fake the watchtower estims for the armageddon
     *
     * @param array $est
     */
    public static function watchtower_arma(array $est): void{
        $town = $est[2];
        $est[0] *= mt_rand($town->getDay(), $town->getDay() + 4);
        $est[1] *= mt_rand($town->getDay() + 3, $town->getDay() + 8);
    }

    /**
     * For the armageddon, we prevent the door being closed by citizens
     *
     * @param [type] $action
     * @return AjaxResponse|null
     */
    public static function door_arma($action): ?AjaxResponse {
        if ($action === "close")
            return AjaxResponse::error( 666666 );
        return null;
    }

    /**
     * For aprils fools, we prevent the door being closed by citizens (different error message than arma)
     *
     * @param [type] $action
     * @return AjaxResponse|null
     */
    public static function door_april($action): ?AjaxResponse {
        if ($action === "close")
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        return null;
    }

    /**
     * For the armageddon and aprils fools, we automatically close the door
     *
     * @param Town $town
     */
    public static function night_arma(Town $town): void {
        if(!$town->getDevastated()) $town->setDoor(false);
    }

    /**
     * For easter, we enable the chocolate cross once the event begins
     *
     * @param Town $town
     * @return bool
     */
    public static function enable_easter(Town $town): bool {
        global $kernel;

        $town_handler = $kernel->getContainer()->get(TownHandler::class);

        $cross = $town_handler->getBuildingPrototype('small_eastercross_#00');
        if (!$cross) return false;

        $gallows = $town_handler->getBuilding($town,'r_dhang_#00', false);
        if ($gallows) $gallows->setPrototype( $cross );

        return true;
    }

    /**
     * For aprils fools, we deposit the black cervical oozing
     *
     * @param Citizen $citizen
     * @return bool
     */
    public static function enable_april(Citizen $citizen): bool {
        global $kernel;

        if (!$citizen->getAlive()) return true;

        $inv_handler  = $kernel->getContainer()->get(InventoryHandler::class);
        $item_factory = $kernel->getContainer()->get(ItemFactory::class);

        $inv_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( 'april_drug_#00' ) );

        return true;
    }

    /**
     * For easter, we disable the chocolate cross once the event ends
     *
     * @param Town $town
     * @return bool
     */
    public static function disable_easter(Town $town): bool {
        global $kernel;

        $town_handler = $kernel->getContainer()->get(TownHandler::class);

        $gallows = $town_handler->getBuildingPrototype('r_dhang_#00');
        if (!$gallows) return false;

        $cross = $town_handler->getBuilding($town,'small_eastercross_#00', false);
        if ($cross) $cross->setPrototype( $gallows );

        return true;
    }

    public static function purge_daysUntil(?\DateTimeInterface $dateTime = null): int {
        if ($dateTime === null) $dateTime = new \DateTime();
        return $dateTime->diff( (new \DateTime('today'))->setDate(2021,9,1) )->d;
    }

    /**
     * Preparation for THE PURGE
     *
     * @param array $est
     */
    public static function watchtower_purge(array $est): void{
        if ($est[3] !== 0) return;
        $dayDiff = self::purge_daysUntil();

        if ($dayDiff > 7 || $dayDiff < 0) return;
        elseif ( $est[4] >= (1.0 - ((7-$dayDiff) / 7) * 0.7) )
            switch ($dayDiff) {
                case 7:case 6:
                    $est[5] = T::__('Vereinzelte Bürger berichten von einem merkwürdigen Phänomen am Himmel... Ihr solltet die Alkoholvorräte in der Bank pürfen.', 'game');
                    break;
                case 5:case 4:
                    $est[5] = T::__('Einige Bürger haben berichtet, während ihrer Abschätzung ein rotes Blitzen am Horizont gesehen zu haben.', 'game');
                    break;
                case 3:case 2:
                    $est[5] = T::__('Ein Großteil der Bürger, die heute auf dem Wachturm waren, haben ein lautes Grollen in der Ferne vernommen.', 'game');
                    break;
                case 1:
                    $est[5] = T::__('Zusatzbemerkung zur heutigen Abschätzung: Der Himmel hat sich blutrot gefärbt. Das sieht nicht gut aus, Leute...', 'game');
                    break;
                case 0:
                    $est[5] = T::__('Das sieht nicht gut aus... Die Zombies werden heute Nacht nicht unser größtes Problem sein.', 'game');
                    break;
            }
    }

    public static function dashboard_purge(array $info) {
        if (self::purge_daysUntil() === 0) {
            $info[1] = array_merge($info[1], [
                T::__('Beten', 'game') => false,
            ]);
            $info[2] = array_merge($info[2], [
                T::__('Es gibt keine Hoffnung!', 'game') => true,
            ]);
        }
    }

    public static function citizen_purge(Town $town): bool {
        global $kernel;
        $death_handler = $kernel->getContainer()->get(DeathHandler::class);

        foreach ($town->getCitizens() as $citizen) {
            if (!$citizen->getAlive()) continue;
            $death_handler->kill($citizen, CauseOfDeath::Apocalypse);
        }

        return true;
    }
}