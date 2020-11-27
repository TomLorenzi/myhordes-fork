<?php


namespace App\Structures;

use App\Entity\Town;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;

class Hook
{
    /**
     * Fake the watchtower estims for the armageddon
     *
     * @param array $est
     */
    public static function watchtower_arma(array $est): void{
        $est[0] += mt_rand(10000, 15000);
        $est[1] += mt_rand(15000, 20000);
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
}