<?php

namespace App\Service\Actions\Game;



use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\Town;
use Doctrine\ORM\EntityManagerInterface;

readonly class CountCitizenProfessionsAction
{
    public function __construct(
        private EntityManagerInterface $em,
    ) { }

    public function __invoke(
        Town $town
    ): array
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(c.id) AS n', 'p.id AS id')
            ->from(Citizen::class, 'c')
            ->leftJoin(CitizenProfession::class, 'p', 'WITH', 'c.profession = p.id')
            ->where('c.town = :town')->setParameter('town', $town)
            ->groupBy('p.id')
            ->getQuery()->getArrayResult();
    }
}