<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class PictoHandler
{
    private $entity_manager;
    private $conf;

    public function __construct(EntityManagerInterface $em, ConfMaster $conf)
    {
        $this->entity_manager = $em;
        $this->conf = $conf;
    }

    public function give_picto(Citizen $citizen, $pictoPrototype, $count = 1){
        if($count == 0) return;

        if(is_string($pictoPrototype)){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoPrototype]);
            if($pictoPrototype === null)
                return;
        }

        // Do not give the Emancipation of the Banished picto to non-banned citizen
        if ($pictoPrototype->getName() === 'r_solban_#00' && !$citizen->getBanished())
            return;

        $is_new = false;
        $picto = $citizen->getUser()->findPicto( 0, $pictoPrototype, $citizen->getTown() );
        if($picto === null){
            $picto = new Picto();
            $is_new = true;
        }
        $picto->setPrototype($pictoPrototype)
            ->setPersisted(0)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setCount($picto->getCount()+$count);
        
        if($is_new)
            $citizen->getUser()->addPicto($picto);

        $this->entity_manager->persist($picto);

    }

    public function give_validated_picto(Citizen $citizen, $pictoPrototype, $count = 1){
        if($count == 0) return;
        
        if(is_string($pictoPrototype)){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoPrototype]);
            if($pictoPrototype === null)
                return;
        }
        
        $is_new = false;
        $picto = $citizen->getUser()->findPicto( 1, $pictoPrototype, $citizen->getTown() );
        if($picto === null){
            $picto = new Picto();
            $is_new = true;
        }

        $picto->setPrototype($pictoPrototype)
            ->setPersisted(1)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setCount($picto->getCount()+$count);
        
        if($is_new)
            $citizen->getUser()->addPicto($picto);

        $this->entity_manager->persist($picto);
    }

    public function nightly_validate_picto(Citizen $citizen) {
        // Also, the RP, Sandball, ban, theft and soul collector pictos are always validated
        // In small town, we add the Guide and Nightwatch pictos
        $pictoAlwaysPersisted = array('r_rp_#00', 'r_sandb_#00', 'r_ban_#00', 'r_theft_#00', 'r_collec_#00');
        if ($citizen->getTown()->getType()->getName() == "small") {
            $pictoAlwaysPersisted = array_merge($pictoAlwaysPersisted, array('r_guide_#00', 'r_guard_#00'));
        }

        foreach ($citizen->getUser()->getPictos() as $picto) {
            /** @var Picto $picto */

            if ($picto->getPersisted() !== 0 || $picto->getTown() !== $citizen->getTown())
                continue;

            $persistPicto = false;
            // We check the day 5 / 8 rule to persist the picto or not
            // To show "You could have earn those if you survived X more days"
            // In Small Towns, if the user has 100 soul points or more, he must survive at least 8 days or die from the attack during day 7 to 8
            // to validate the picto (set them as persisted)
            if(in_array($picto->getPrototype()->getName(), $pictoAlwaysPersisted)){
                $persistPicto = true;
            } else if ($citizen->getTown()->getType()->getName() == "small" && $citizen->getUser()->getAllSoulPoints() >= 100) {
                if($citizen->getSurvivedDays() == 8 && $citizen->getCauseOfDeath() !== null && $citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack)
                    $persistPicto = true;
                else if ($citizen->getSurvivedDays() > 8)
                    $persistPicto = true;
            } else if ($citizen->getSurvivedDays() >= 5) {
                $persistPicto = true;
            }

            if (!$persistPicto) continue;

            // We check if this picto has already been earned previously (such as Heroic Action, 1 per day)
            $previousPicto = $citizen->getUser()->findPicto( 1, $picto->getPrototype(), $citizen->getTown() );
            if ($previousPicto === null) {
                // We do not have it, we set it as earned
                $picto->setPersisted(1);
                $this->entity_manager->persist($picto);
            } else {
                // We have it, we add the count to the previously earned
                // And remove the picto from today
                $previousPicto->setCount($previousPicto->getCount() + $picto->getCount());
                $this->entity_manager->persist($previousPicto);
                $this->entity_manager->remove($picto);
            }
        }
    }

    public function validate_picto(Citizen $citizen) {
        $this->nightly_validate_picto($citizen);

        $conf = $this->conf->getTownConfiguration($citizen->getTown());

        // In private towns, we get only 1/3 of all pictos and no rare, unless it is specified otherwise
        if($citizen->getTown()->getType()->getName() == "custom" && !$conf->get(TownConf::CONF_FEATURE_GIVE_ALL_PICTOS, false)){

            $keepPictos = [];

            foreach ($citizen->getUser()->getPictos() as $picto) {
                /** @var Picto $picto */

                if ($picto->getTown() !== $citizen->getTown())
                    continue;

                if($picto->getPrototype()->getRare()) {
                    $this->entity_manager->remove($picto);
                    continue;
                }
                
                if($picto->getPrototype()->getName() !== "r_ptame_#00")
                    $keepPictos[] = $picto;
                else {
                    $picto->setCount(ceil($picto->getCount() / 3));
                    $this->entity_manager->persist($picto);
                }
            }

            shuffle($keepPictos);

            for($i = ceil(count($keepPictos) / 3); $i < count($keepPictos); $i++)
                $this->entity_manager->remove($keepPictos[$i]);
        }
    }

    public function has_picto(Citizen $citizen, $pictoPrototype){
        if(is_string($pictoPrototype)){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoPrototype]);
            if($pictoPrototype === null)
                return false;
        }

        foreach ($citizen->getUser()->getPictos() as $picto)
            if ($picto->getPrototype() === $pictoPrototype)
                return true;

        return false;
    }
}
