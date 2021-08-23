<?php
namespace App\Tests\Service;

use App\Service\GameFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameFactoryTest extends KernelTestCase
{
    public function testTownCreation()
    {
        // (1) boot the Symfony kernel
        self::bootKernel([
            'debug'       => false,
        ]);

        // (2) use static::getContainer() to access the service container
        $container = self::$container;

        // (3) run some service & test the result
        $gameFactory = $container->get(GameFactory::class);
        /** @var GameFactory $gameFactory */

        // Let's try to create a french remote town with a generated name and 40 citizens in ut
        $town = $gameFactory->createTown(null, "fr", 40, "remote");

        $this->assertNotNull($town);

        self::assertEquals(40, $town->getPopulation());
        self::assertEquals("fr", $town->getLanguage());

    }
}