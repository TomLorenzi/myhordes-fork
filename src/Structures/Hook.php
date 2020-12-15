<?php


namespace App\Structures;

use App\Entity\Citizen;
use App\Entity\Town;
use App\Response\AjaxResponse;

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
     * For the armageddon, we automatically close the door
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
    public static function night_christmas($town) {
        /** @var Town $town */
        foreach ($town[0]->getCitizens() as $citizen){
            /** @var Citizen $citizen */
        }
    }
}