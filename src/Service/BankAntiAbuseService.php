<?php

namespace App\Service;

use App\Entity\BankAntiAbuse;
use App\Entity\Citizen;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class BankAntiAbuseService {

    private EntityManagerInterface $em;
    private ConfMaster $conf;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->conf = $conf;
    }

    public function increaseBankCount(Citizen $citizen) {
        if (($bankAntiAbuse = $citizen->getBankAntiAbuse()) === null) {
            $this->em->persist($bankAntiAbuse =
                (new BankAntiAbuse())
                    ->setCitizen($citizen)
                    ->setNbItemTaken(1)
                    ->setUpdated(new \DateTime())
            );
            $citizen->setBankAntiAbuse($bankAntiAbuse);
        } else {
            if ($this->inRangeOfTaking($citizen, $bankAntiAbuse->getUpdated()))
                $bankAntiAbuse->increaseNbItemTaken();
            else
                $bankAntiAbuse->setNbItemTaken(1);
            $bankAntiAbuse->setUpdated(new \DateTime());
            $this->em->persist($bankAntiAbuse);
        }
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