<?php

namespace App\Service;

use App\Entity\BankAntiAbuse;
use App\Entity\Citizen;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class BankAntiAbuseService {

    private $em;
    private $conf;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->conf = $conf;
    }

    public function increaseBankCount(Citizen $citizen) {

        $bankAntiAbuse = $citizen->getBankAntiAbuse();

        if (is_null($bankAntiAbuse))
        {
            $bankAntiAbuse = new BankAntiAbuse();
            $bankAntiAbuse->setCitizen($citizen);
            $bankAntiAbuse->setUpdated(new \DateTime());
        }

        if ($this->inRangeOfTaking($bankAntiAbuse->getUpdated())) {
            $bankAntiAbuse->increaseNbItemTaken();
        } else {
            $bankAntiAbuse->setNbItemTaken(1);
        }

        $this->em->persist($bankAntiAbuse);
    }

    public function allowedToTake(Citizen $citizen): bool {

        $town = $citizen->getTown();

        $nbObjectMax = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_BANK_ABUSE_LIMIT);
        $bankAntiAbuse = $citizen->getBankAntiAbuse();

        // In chaos mode you can take twice as many
        if ($town->getChaos())
        {
            $nbObjectMax = $nbObjectMax*2;
        }

        if ($bankAntiAbuse !== null && $this->inRangeOfBan($bankAntiAbuse->getUpdated()) && $bankAntiAbuse->getNbItemTaken() >= $nbObjectMax) {
            return false;
        }

        return true;
    }
    
    // todo: make this configurable too.
    private function inRangeOfTaking(\DateTime $lastUpdate): bool {
        return abs(($lastUpdate->getTimestamp() - (new \DateTime)->getTimestamp()) / 60) < 5;
    }

    private function inRangeOfBan(\DateTime $lastUpdate): bool {
        return abs(($lastUpdate->getTimestamp() - (new \DateTime)->getTimestamp()) / 60) < 15;
    }
}