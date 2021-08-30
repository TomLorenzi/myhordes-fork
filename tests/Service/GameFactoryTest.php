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

        // We try to create a 100 towns
        for ($i = 0; $i < 100 ; $i++) {
            // Let's try to create a french remote town with a generated name and 40 citizens in it
            $town = $gameFactory->createTown(null, "fr", 40, "remote");

            $this->assertNotNull($town);

            self::assertEquals(40, $town->getPopulation());
            self::assertEquals("fr", $town->getLanguage());

            foreach ($town->getZones() as $zone) {
                if ($zone->getPrototype() === null) continue;

                self::assertLessThanOrEqual($zone->getPrototype()->getMaxDistance(), $zone->getDistance(), "Town $i/100 : Zone at [{$zone->getX()}, {$zone->getY()}] has ruin {$zone->getPrototype()->getLabel()}, is at {$zone->getDistance()}km of town but the ruin must be between {$zone->getPrototype()->getMinDistance()}km and {$zone->getPrototype()->getMaxDistance()}km");
                self::assertGreaterThanOrEqual($zone->getPrototype()->getMinDistance(), $zone->getDistance(), "Town $i/100 : Zone at [{$zone->getX()}, {$zone->getY()}] has ruin {$zone->getPrototype()->getLabel()}, is at {$zone->getDistance()}km of town but the ruin must be between {$zone->getPrototype()->getMinDistance()}km and {$zone->getPrototype()->getMaxDistance()}km");
            }
        }
    }
}