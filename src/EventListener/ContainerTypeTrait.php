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
     * @return object The service class.
     * @psalm-return T
     *
     * @template T as object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getService(string $service): object {
        return $this->container->get($service);
    }
}