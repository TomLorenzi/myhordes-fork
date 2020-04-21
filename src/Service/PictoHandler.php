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

    public function give_picto(Citizen &$citizen, PictoPrototype $pictoPrototype, $count = 1){
        $picto = $this->entity_manager->getRepository(Picto::class)->findTodayPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pictoPrototype);
        echo "Giving picto {$pictoPrototype->getLabel()} to {$citizen->getUser()->getUsername()}\n";
        if($picto === null) $picto = new Picto();
        $picto->setPrototype($pictoPrototype)
            ->setPersisted(0)
            ->setTown($citizen->getTown())
            ->setUser($citizen->getUser())
            ->setCount($picto->getCount()+$count);

        $this->entity_manager->persist($picto);
        $this->entity_manager->flush();
    }
}
