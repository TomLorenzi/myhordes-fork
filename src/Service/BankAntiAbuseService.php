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

        if (($bankAntiAbuse = $citizen->getBankAntiAbuse()) === null)
        {
            $bankAntiAbuse = new BankAntiAbuse();
            $bankAntiAbuse->setCitizen($citizen);
            $bankAntiAbuse->setUpdated(new \DateTime());
        }

        if ($this->inRangeOfTaking($citizen, $bankAntiAbuse->getUpdated()))
            $bankAntiAbuse->increaseNbItemTaken();
        else
            $bankAntiAbuse->setNbItemTaken(1);

        $this->em->persist($bankAntiAbuse);
    }

    public function allowedToTake(Citizen $citizen): bool {

        $town = $citizen->getTown();
        $bankAntiAbuse = $citizen->getBankAntiAbuse();

        if ($bankAntiAbuse === null) return true;

        $nbObjectMax = $this->conf->getTownConfiguration($town)->get($town->getChaos() ? TownConf::CONF_BANK_ABUSE_LIMIT_CHAOS : TownConf::CONF_BANK_ABUSE_LIMIT, 5);
        return !($this->inRangeOfBan($citizen, $bankAntiAbuse->getUpdated()) && $bankAntiAbuse->getNbItemTaken() >= $nbObjectMax);
    }

    private function inRangeOfTaking(Citizen $citizen, \DateTime $lastUpdate): bool {
        $limit = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_BANK_ABUSE_BASE, 5);
        return abs(($lastUpdate->getTimestamp() - (new \DateTime)->getTimestamp()) / 60) < $limit;
    }

    private function inRangeOfBan(Citizen $citizen, \DateTime $lastUpdate): bool {
        $limit = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_BANK_ABUSE_LOCK, 15);
        return abs(($lastUpdate->getTimestamp() - (new \DateTime)->getTimestamp()) / 60) < $limit;
    }
}