<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class DoctrineCacheService {


    private array $cache = [];

    public function __construct(private readonly EntityManagerInterface $em)
    { }

    private function init(): void
    {
        $this->cache = [
            'by-single-identifier' => []
        ];
    }

    public function clearAll(): void
    {
        $this->init();
    }

    /**
     * Returns a single entity identified by its class, a field name and field value. The returned entity is cached, so
     * subsequent requests for the same entity instance will not incur additional database queries.
     *
     * @param string $class The class name of the entity.
     * @psalm-param class-string<T> $class
     *
     * @return object|null The entity, or null if the entity was not found
     * @psalm-return T
     *
     * @template T of object
     */
    public function getEntityByIdentifier( string $class, string $identifier, string $field = 'name' ): ?object {
        if (!isset( $this->cache['by-single-identifier'][$class] )) $this->cache['by-single-identifier'][$class] = [];
        if (!isset( $this->cache['by-single-identifier'][$class][$field] )) $this->cache['by-single-identifier'][$class][$field] = [];

        return $this->cache['by-single-identifier'][$class][$field][$identifier] ??
            ($this->cache['by-single-identifier'][$class][$field][$identifier] = $this->em->getRepository($class)->findOneBy([$field => $identifier]));
    }

}