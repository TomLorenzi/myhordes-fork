<?php


namespace App\Service;


use App\Entity\TownClass;
use Doctrine\ORM\EntityManagerInterface;

class GameValidator
{
    private $entity_manager;
    private $cache_valid_town_types = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    public function getValidTownTypes( ): array {
        return $this->cache_valid_town_types ?: ( $this->cache_valid_town_types = array_map(function(TownClass $entry) {
            return $entry->getName();
        }, $this->entity_manager->getRepository(TownClass::class)->findAll()));
    }

    public function validateTownType( string $type ): bool {
        return in_array($type, $this->getValidTownTypes());
    }

    public function validateTownPopulation( int $pop, string $type ): bool {
        if ($pop <= 0) return false;
        switch ($type) {
            case 'small': return $pop <= 20;
            case 'remote':case 'panda': return $pop <= 40;
            case 'custom': return $pop <= 120;
            default: return false;
        }
    }
}