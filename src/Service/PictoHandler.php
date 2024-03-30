<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Enum\Configuration\TownSetting;
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

    public function award_picto_to( Citizen $citizen, string|PictoPrototype $pictoPrototype, int $count = 1, ?bool $persist = null ): bool {

        if ($count <= 0) return false;

        $conf = $this->conf->getTownConfiguration($citizen->getTown());
        if (!$conf->get(TownConf::CONF_FEATURE_PICTOS, true)) return false;

        if( is_string($pictoPrototype) ){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoPrototype]);
            if ($pictoPrototype === null) return false;
        }

        // Do not give the Emancipation of the Banished picto to non-banned citizen
        if ($pictoPrototype->getName() === 'r_solban_#00' && !$citizen->getBanished())
            return false;

        if ($persist === null) {

            if (in_array($pictoPrototype->getName(), $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_INSTANT_PICTOS, [])))
                $persist = true;
            else {
                $dayLimit = ($this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_STRICT_PICTOS, false) && $citizen->getUser()->getAllSoulPoints() >= 100) ? 8 : 5;
                $persist = $citizen->getTown()->getDay() >= $dayLimit;
            }

        }

        $is_new = false;
        $picto = $citizen->getUser()->findPicto( $persist ? 1 : 0, $pictoPrototype, $citizen->getTown() );
        if( $picto === null ){
            $picto = new Picto();
            $is_new = true;
        }
        $picto->setPrototype($pictoPrototype)
            ->setPersisted($persist ? 1 : 0)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setOld( $citizen->getTown()->getSeason() === null )
            ->setCount($picto->getCount()+$count);

        if($is_new)
            $citizen->getUser()->addPicto($picto);

        $this->entity_manager->persist($picto);
        return true;
    }

    public function give_picto(Citizen $citizen, $pictoPrototype, $count = 1): void{
        $this->award_picto_to( $citizen, $pictoPrototype, $count );
    }

    public function give_validated_picto(Citizen $citizen, $pictoPrototype, $count = 1): void{
        $this->award_picto_to( $citizen, $pictoPrototype, $count, true );
    }

    public function nightly_validate_picto(Citizen $citizen):void {
        // Also, the RP, Sandball, ban, theft and soul collector pictos are always validated
        // In small town, we add the Guide and Nightwatch pictos
        $pictoAlwaysPersisted = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_INSTANT_PICTOS, []);

        $pictos = $this->entity_manager->getRepository(Picto::class)->findBy(['user' => $citizen->getUser(), 'town' => $citizen->getTown(), "persisted" => 0]);

        // We check the day 8 rule to persist the picto or not
        // To show "You could have earn those if you survived X more days"
        // In Small Towns, if the user has 100 soul points or more, he must survive at least 8 days or die from the attack during day 7 to 8
        // to validate the picto (set them as persisted)
        $update_persistance = !($this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_STRICT_PICTOS, false) && $citizen->getUser()->getAllSoulPoints() >= 100) || $citizen->getTown()->getDay() >= 8;

        foreach ($pictos as $picto) {
            /** @var Picto $picto */
            if (!$update_persistance && !in_array($picto->getPrototype()->getName(), $pictoAlwaysPersisted)) continue;

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
                $picto->setCount(0);
                $citizen->getUser()->removePicto($picto);
                $this->entity_manager->remove($picto);
            }
        }
    }

    public function validate_picto(Citizen $citizen) {
        $conf = $this->conf->getTownConfiguration($citizen->getTown());

        // In private towns, we get only 1/3 of all pictos and no rare, unless it is specified otherwise
        if(!$conf->get(TownConf::CONF_FEATURE_GIVE_ALL_PICTOS, true)){

            $keepPictos = [];
            $pictos = $this->entity_manager->getRepository(Picto::class)->findBy(['user' => $citizen->getUser(), 'town' => $citizen->getTown(), "persisted" => 1]);

            foreach ($pictos as $picto) {
                /** @var Picto $picto */

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

            if ($conf->get( TownSetting::PictoClassicCullMode )) {

                foreach ($keepPictos as $picto) {
                    /** @var Picto $picto */
                    if ($picto->getPrototype()->getRare()) {
                        $new_count = floor($picto->getCount() / 3);
                        if ($new_count <= 0) $this->entity_manager->remove( $picto );
                        else $picto->setCount(floor($picto->getCount() / 3));
                        $this->entity_manager->persist($picto->setCount($new_count));
                    }
                }

            } else {
                shuffle($keepPictos);

                for($i = ceil(count($keepPictos) / 3); $i < count($keepPictos); $i++)
                    $this->entity_manager->remove($keepPictos[$i]);
            }


        }
    }
}
