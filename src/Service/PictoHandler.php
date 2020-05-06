<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class PictoHandler
{
    private $entity_manager;

    public function __construct(
        EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    public function give_picto(Citizen &$citizen, $pictoPrototype, $count = 1){
        if(is_string($pictoPrototype)){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName($pictoPrototype);
            if($pictoPrototype === null)
                return;
        }
        $picto = $this->entity_manager->getRepository(Picto::class)->findTodayPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pictoPrototype);
        if($picto === null) $picto = new Picto();
        $picto->setPrototype($pictoPrototype)
            ->setPersisted(0)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setCount($picto->getCount()+$count);

        $this->entity_manager->persist($picto);
        $this->entity_manager->flush();
    }

    public function give_validated_picto(Citizen &$citizen, $pictoPrototype, $count = 1){
        if(is_string($pictoPrototype)){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName($pictoPrototype);
            if($pictoPrototype === null)
                return;
        }
        $picto = $this->entity_manager->getRepository(Picto::class)->findPreviousDaysPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pictoPrototype);
        if($picto === null) $picto = new Picto();
        $picto->setPrototype($pictoPrototype)
            ->setPersisted(1)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setCount($picto->getCount()+$count);

        $this->entity_manager->persist($picto);
        $this->entity_manager->flush();
    }

    public function validate_picto(Citizen $citizen){
        // Also, the RP, Sandball, ban, theft and soul collector pictos are always validated
        // In small town, we add the Guide and Nightwatch pictos
        $pictoAlwaysPersisted = array('r_rp_#00', 'r_sandb_#00', 'r_ban_#00', 'r_theft_#00', 'r_collec_#00');
        if($citizen->getTown()->getType()->getName() == "small"){
            $pictoAlwaysPersisted = array_merge($pictoAlwaysPersisted, array('r_guide_#00', 'r_guard_#00'));
        }

        $pictoRepository = $this->entity_manager->getRepository(Picto::class);

        $pendingPictos = $pictoRepository->findTodayPictoByUserAndTown($citizen->getUser(), $citizen->getTown());

        foreach ($pendingPictos as $pendingPicto) {
	        $persistPicto = false;
	        // We check the day 5 / 8 rule to persist the picto or not
	        // To show "You could have earn those if you survived X more days"
	        // In Small Towns, if the user has 100 soul points or more, he must survive at least 8 days or die from the attack during day 7 to 8
	        // to validate the picto (set them as persisted)
	        if($pendingPicto->getPersisted() == 1 || in_array($pendingPicto->getPrototype()->getName(), $pictoAlwaysPersisted)){
	        	$persistPicto = true;
	        } else if($citizen->getTown()->getType()->getName() == "small" && $citizen->getUser()->getSoulPoints() >= 100) {
	            if($citizen->getSurvivedDays() == 8 && $citizen->getCauseOfDeath() != null && $citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack){
	                $persistPicto = true;
	            } else if ($citizen->getSurvivedDays() > 8) {
	                $persistPicto = true;
	            }
	        } else if($citizen->getSurvivedDays() >= 5) {
	            $persistPicto = true;
	        }

	        if (!$persistPicto)
	            continue;

	        // We check if this picto has already been earned previously (such as Heroic Action, 1 per day)
	        $previousPicto = $pictoRepository->findPreviousDaysPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pendingPicto->getPrototype());
	        if($previousPicto === null) {
	            // We do not have it, we set it as earned
	            $pendingPicto->setPersisted(1);
	            $this->entity_manager->persist($pendingPicto);
	        } else {
	            // We have it, we add the count to the previously earned
	            // And remove the picto from today
	            $previousPicto->setCount($previousPicto->getCount() + $pendingPicto->getCount());
	            $this->entity_manager->persist($previousPicto);
	            $this->entity_manager->remove($pendingPicto);
	        }
        }
        $this->entity_manager->flush();
    }
}
