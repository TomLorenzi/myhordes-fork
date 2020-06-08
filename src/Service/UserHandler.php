<?php

namespace App\Service;

use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UserHandler
{
    private $entity_manager;

    public function __construct( EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    public function getPoints(User $user){
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
        $points = 0;

        if($user->getSoulPoints() >= 100) {
            $points += 13;
        }
        if($user->getSoulPoints() >= 500) {
            $points += 33;
        }
        if($user->getSoulPoints() >= 1000) {
            $points += 66;
        }
        if($user->getSoulPoints() >= 2000) {
            $points += 132;
        }
        if($user->getSoulPoints() >= 3000) {
            $points += 198;
        }

        foreach ($pictos as $picto) {
            switch($picto["name"]){
                case "r_heroac_#00": case "r_explor_#00":
                    if ($picto["c"] >= 15)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_cookr_#00": case "r_cmplst_#00": case "r_camp_#00": case "r_drgmkr_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 25)
                        $points += 6.5;
                    break;
                case "r_animal_#00":
                    if ($picto["c"] >= 30)
                        $points += 3.5;
                    if ($picto["c"] >= 60)
                        $points += 6.5;
                    break;
                case "r_chstxl_#00": case "r_ruine_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    break;
                case "r_build_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 200)
                        $points += 6.5;
                    break;
                case "status_clean_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 75)
                        $points += 6.5;
                    break;
                case "r_ebuild_#00":
                    if ($picto["c"] >= 1)
                        $points += 3.5;
                    if ($picto["c"] >= 3)
                        $points += 6.5;
                    break;
                case "r_digger_#00":
                    if ($picto["c"] >= 50)
                        $points += 3.5;
                    if ($picto["c"] >= 300)
                        $points += 6.5;
                    break;
                case "r_deco_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 250)
                        $points += 6.5;
                    break;
                case "r_explo2_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    break;
                case "r_guide_#00":
                    if ($picto["c"] >= 300)
                        $points += 3.5;
                    if ($picto["c"] >= 1000)
                        $points += 6.5;
                    break;
                case "r_theft_#00": case "r_jtamer_#00": case "r_jrangr_#00": case "r_jguard_#00": case "r_jermit_#00":
                case "r_jtech_#00": case "r_jcolle_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_maso_#00": case "r_guard_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 40)
                        $points += 6.5;
                    break;
                case "r_surlst_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    if ($picto["c"] >= 30)
                        $points += 10;
                    if ($picto["c"] >= 50)
                        $points += 13;
                    if ($picto["c"] >= 100)
                        $points += 16.5;
                    break;
                case "r_suhard_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    if ($picto["c"] >= 20)
                        $points += 10;
                    if ($picto["c"] >= 40)
                        $points += 13;
                    break;
                case "r_doutsd_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    break;
                case "r_door_#00":
                    if($picto["c"] >= 1)
                        $points += 3.5;
                    if($picto["c"] >= 5)
                        $points += 6.5;
                    break;
                case "r_wondrs_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    if($picto["c"] >= 50)
                        $points += 6.5;
                    break;
                case "r_rp_#00":
                    if($picto["c"] >= 5)
                        $points += 3.5;
                    if($picto["c"] >= 10)
                        $points += 6.5;
                    if($picto["c"] >= 20)
                        $points += 10;
                    if($picto["c"] >= 30)
                        $points += 13;
                    if($picto["c"] >= 40)
                        $points += 16.5;
                    if($picto["c"] >= 60)
                        $points += 20;
                    break;
                case "r_winbas_#00":
                    if($picto["c"] >= 2)
                        $points += 13;
                    if($picto["c"] >= 5)
                        $points += 20;
                    break;
                case "r_wintop_#00":
                    if($picto["c"] >= 1)
                        $points += 20;
                    break;
                case "small_zombie_#00":
                    if($picto["c"] >= 100)
                        $points += 3.5;
                    if($picto["c"] >= 200)
                        $points += 6.5;
                    if($picto["c"] >= 300)
                        $points += 10;
                    if($picto["c"] >= 800)
                        $points += 13;
                    break;
            }
        }

        return $points;
    }

    public function hasSkill(User $user, $skill){
        if(is_string($skill)) {
            $skill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $skill]);
            if($skill === null)
                return false;
        }

        $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($user->getHeroDaysSpent());
        return in_array($skill, $skills);
    }
}