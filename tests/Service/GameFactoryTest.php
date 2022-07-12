<?php
namespace App\Tests\Service;

use App\Entity\CitizenProfession;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\Translator;

class GameFactoryTest extends KernelTestCase
{
    private EntityManagerInterface|null $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel([
            'debug'       => false,
        ]);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testTownCreation()
    {
        // (1) boot the Symfony kernel
        self::bootKernel([
            'debug'       => false,
        ]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        /** @var GameFactory $gameFactory */
        $gameFactory = $container->get(GameFactory::class);
        $random = $container->get(RandomGenerator::class);

        // We try to create a 100 towns
        for ($i = 0; $i < 100 ; $i++) {

            $lang =  $random->pick(['en', 'fr', 'de', 'es', 'multi']);
            $type = $random->pick(['small', 'remote', 'panda', 'invalid']);
            // Let's try to create a french remote town with a generated name and 40 citizens in it
            $town = $gameFactory->createTown(null, $lang, 40, $type);

            if ($type !== "invalid")
                $this->assertNotNull($town);
            else {
                $this->assertNull($town);
                continue;
            }

            self::assertEquals(40, $town->getPopulation());
            self::assertEquals($lang, $town->getLanguage());

            foreach ($town->getZones() as $zone) {
                if ($zone->getPrototype() === null) continue;

                self::assertLessThanOrEqual($zone->getPrototype()->getMaxDistance(), $zone->getDistance(), "Town $i/100 : Zone at [{$zone->getX()}, {$zone->getY()}] has ruin {$zone->getPrototype()->getLabel()}, is at {$zone->getDistance()}km of town but the ruin must be between {$zone->getPrototype()->getMinDistance()}km and {$zone->getPrototype()->getMaxDistance()}km");
                self::assertGreaterThanOrEqual($zone->getPrototype()->getMinDistance(), $zone->getDistance(), "Town $i/100 : Zone at [{$zone->getX()}, {$zone->getY()}] has ruin {$zone->getPrototype()->getLabel()}, is at {$zone->getDistance()}km of town but the ruin must be between {$zone->getPrototype()->getMinDistance()}km and {$zone->getPrototype()->getMaxDistance()}km");
            }
        }
    }

    public function testCitizenCreation() {
        // (1) boot the Symfony kernel
        self::bootKernel([
            'debug'       => false,
        ]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        /** @var GameFactory $gameFactory */
        $gameFactory = $container->get(GameFactory::class);
        $citizenHandler = $container->get(CitizenHandler::class);
        $random = $container->get(RandomGenerator::class);

        // Let's create a small town
        $town = $gameFactory->createTown(null, "en", 40, "small");
        $users = $this->entityManager->getRepository(User::class)->findBy([], [], $town->getPopulation());

        foreach ($users as $user) {
            $errors = 0;
            $citizen = $gameFactory->createCitizen($town, $user, $errors);

            self::assertNotNull($citizen);
            self::assertEquals(0, $errors);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

}