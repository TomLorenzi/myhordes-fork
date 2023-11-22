<?php
namespace App\Tests\Service;

use App\Entity\CitizenProfession;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\RandomGenerator;
use App\Structures\TownSetup;
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

		if ('test' !== $kernel->getEnvironment()) {
			throw new LogicException('Execution only in Test environment possible!');
		}

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
            $town = $gameFactory->createTown(new TownSetup( $type, language: $lang, population: 40));

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
        $town = $gameFactory->createTown(new TownSetup('small', language: 'en', population: 40));
        $users = $this->entityManager->getRepository(User::class)->findBy([], [], $town->getPopulation());

        foreach ($users as $user) {
            $errors = 0;
            $citizen = $gameFactory->createCitizen($town, $user, $errors);

            self::assertNotNull($citizen);
            self::assertEquals(0, $errors);
        }
    }

    public function testInitialDrops() {
        // (1) boot the Symfony kernel
        self::bootKernel([
            'debug'       => false,
        ]);

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        /** @var GameFactory $gameFactory */
        $gameFactory = $container->get(GameFactory::class);

        // Let's create some towns.
        $smallTown = $gameFactory->createTown( new TownSetup('small',  language: 'en', population: 40));
        $pandaTown = $gameFactory->createTown( new TownSetup('panda',  language: 'fr', population: 40));
        $remoteTown = $gameFactory->createTown(new TownSetup('remote', language: 'de', population: 40));

        // Creating basic stuff.
        $bplanBoxes = ['bplan_box_e_#00', 'bplan_box_e_#00', 'bplan_box_#00', 'bplan_box_#00', 'bplan_box_#00', 'bplan_box_#00', 'bplan_box_#00'];
        $fireworkItems = ['firework_powder_#00', 'firework_tube_#00', 'firework_box_#00', 'firework_box_#00'];

        // We sort our arrays because assertEquals will also compare order.
        sort($bplanBoxes);
        sort($fireworkItems);

        $townBplanBoxes = array();
        $townFireworkItems = array();

        foreach ($smallTown->getZones() as $zone) {
            $items = $zone->getFloor()->getItems()->toArray();

            if (empty($items)) continue;

            foreach ($items as $item) {
                if (in_array($item->getPrototype()->getName(), $bplanBoxes)) {
                    array_push($townBplanBoxes, $item->getPrototype()->getName());
                } else if (in_array($item->getPrototype()->getName(), $fireworkItems)) {
                    array_push($townFireworkItems, $item->getPrototype()->getName());
                } else {
                    // New item ? Modify test...
                    self::fail("Found " . $item->getPrototype()->getName() . " on the map but this item is not tested. Please modify the test.");
                }
            }
        }

        // Check that everything is ok.
        sort($townBplanBoxes);
        sort($townFireworkItems);
        self::assertEquals($bplanBoxes, $townBplanBoxes, "Wrong bplan boxes in small towns.");
        self::assertEquals($fireworkItems, $townFireworkItems, "Wrong firework items in small towns.");

        // Go to panda towns now.
        $townBplanBoxes = array();
        $townFireworkItems = array();

        foreach ($pandaTown->getZones() as $zone) {
            $items = $zone->getFloor()->getItems()->toArray();

            if (empty($items)) continue;

            foreach ($items as $item) {
                if (in_array($item->getPrototype()->getName(), $bplanBoxes)) {
                    array_push($townBplanBoxes, $item->getPrototype()->getName());
                } else if (in_array($item->getPrototype()->getName(), $fireworkItems)) {
                    array_push($townFireworkItems, $item->getPrototype()->getName());
                } else {
                    // New item ? Modify test...
                    self::fail("Found ".$item->getPrototype()->getName()." on the map but this item is not tested. Please modify the test.");
                }
            }
        }

        sort($townBplanBoxes);
        sort($townFireworkItems);
        self::assertEquals($bplanBoxes, $townBplanBoxes, "Wrong bplan boxes in panda towns.");
        self::assertEquals($fireworkItems, $townFireworkItems, "Wrong firework items in panda towns.");

        // Go to remote towns now.
        $townBplanBoxes = array();
        $townFireworkItems = array();

        foreach ($remoteTown->getZones() as $zone) {
            $items = $zone->getFloor()->getItems()->toArray();

            if (empty($items)) continue;

            foreach ($items as $item) {
                if (in_array($item->getPrototype()->getName(), $bplanBoxes)) {
                    array_push($townBplanBoxes, $item->getPrototype()->getName());
                } else if (in_array($item->getPrototype()->getName(), $fireworkItems)) {
                    array_push($townFireworkItems, $item->getPrototype()->getName());
                } else {
                    // New item ? Modify test...
                    self::fail("Found " . $item->getPrototype()->getName() . " on the map but this item is not tested. Please modify the test.");
                }
            }
        }

        sort($townBplanBoxes);
        sort($townFireworkItems);
        self::assertEquals($bplanBoxes, $townBplanBoxes, "Wrong bplan boxes in remote towns.");
        self::assertEquals($fireworkItems, $townFireworkItems, "Wrong firework items in remote towns.");
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

}