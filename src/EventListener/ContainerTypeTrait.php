<?php

namespace App\EventListener;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ContainerTypeTrait
{
    private readonly ContainerInterface $container;

    /**
     * Fetches a service from the service container
     *
     * @param string $service The name of the service class.
     * @psalm-param class-string<T> $service
     *
     * @return null|object The service class.
     * @psalm-return T
     *
     * @template T as object
     */
    protected function getService(string $service): ?object {
        try {
            return $this->container->get($service);
        } catch (\Throwable $t) {
            return null;
        }

    }
}