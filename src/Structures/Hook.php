<?php


namespace App\Structures;

use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Town;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;

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
     * For christmas, if we're on the 25th or 31st, we spawn items
     *
     * @param Town $town
     */
    public static function night_xmas(Town $town): void {
        if ((int)date('m') !== 12 || (int)date('j') !== 25) return;

        global $kernel;

        $citizen_handler   = $kernel->getContainer()->get(CitizenHandler::class);
        $inventory_handler = $kernel->getContainer()->get(InventoryHandler::class);
        $item_factory      = $kernel->getContainer()->get(ItemFactory::class);

        foreach ($town->getCitizens() as $citizen) {
            if (!$citizen->getAlive() || $citizen_handler->hasStatusEffect($citizen, 'tg_got_xmas_gift')) continue;

            $citizen_handler->inflictStatus( $citizen, 'tg_got_xmas_gift' );
            $inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( 'chest_christmas_3_#00' ) );
        }
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

        if (!$citizen->getActive()) return true;

        $inv_handler  = $kernel->getContainer()->get(InventoryHandler::class);
        $item_factory = $kernel->getContainer()->get(ItemFactory::class);

        $inv_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( 'april_drug_#00' ) );

        return true;
    }

    /**
     * For easter, we enable the chocolate cross once the event begins
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
}