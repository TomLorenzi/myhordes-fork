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
     * @param array $estims
     * @return array
     */
    public static function watchtower_arma($estims){
        $estims["min"] += mt_rand(10000, 15000); 
        $estims["max"] += mt_rand(15000, 20000);
        return $estims;
    }

    /**
     * For the armageddon, we prevent the door being closed by citizens
     *
     * @param [type] $action
     * @return AjaxResponse|null
     */
    public static function door_arma($action){
        if ($action === "close")
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        return null;
    }

    /**
     * For the armageddon, we automatically close the door
     *
     * @param Town $town
     */
    public static function night_arma($town) {
        /** @var Town $town */
        if(!$town[0]->getDevastated())
            $town[0]->setDoor(false);
    }
}