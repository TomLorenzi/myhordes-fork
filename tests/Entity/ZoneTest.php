<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Enum\ZoneActivityMarkerType;
use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function PHPUnit\Framework\assertEquals;

final class ZoneTest extends KernelTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;


	/**
	 * @throws ToolsException
	 */
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

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
		dump($this->entityManager->getMetadataFactory()->getAllMetadata());
    }



    public function testZoneScoutLevel(): void
    {
        list($zone, $citizens) = $this->prepareZoneAndCitizens();

        for($j = 0; $j <= 3; $j++){
            foreach($citizens as $citizen){
                self::assertLessThanOrEqual(3-$j, abs($zone->getPersonalScoutEstimation($citizen)));
            }
            self::assertEquals($j, $zone->getScoutLevel());

            for($i = 0; $i < 5; $i++){
                self::assertEquals($i+($j*5), $zone->getActivityMarkersFor(ZoneActivityMarkerType::ScoutVisit)->count());
                $marker = (new ZoneActivityMarker())
                    ->setCitizen($citizens[0])
                    ->setTimestamp(new DateTime())
                    ->setType(ZoneActivityMarkerType::ScoutVisit);
                $zone->addActivityMarker($marker);
                $this->entityManager->persist($marker);
                $this->entityManager->persist($zone);
                $this->entityManager->flush();
                $this->entityManager->refresh($zone);

            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function prepareZoneAndCitizens(): array
    {
        $town = $this->setUpTown();

        $zone = new Zone();
        $zone->setFloor(new Inventory());
        $zone->setTown($town);
        $zone->setX(1);
        $zone->setY(1);
        $zone->setZombies(0);
        $zone->setInitialZombies(0);
        $zone->setScoutEstimationOffset(0);
        $this->entityManager->persist($zone);
        $citizens = [];
        list($job, $tent, $cause) = $this->setUpCitizenProtos();


        for ($k = 0; $k <= 100; $k++) {
            $c = $this->setUpCitizen($k, $job, $town, $tent, $cause);

            $citizens[] = $c;
            $this->entityManager->persist($c);
        }
        $this->entityManager->flush();
        return array($zone, $citizens);
    }

    /**
     * @return Town
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function setUpTown(): Town
    {
        $town = new Town();
        $townClass = new TownClass();
        $townClass->setName("small");
        $townClass->setLabel("small");
        $townClass->setRanked(true);
        $townClass->setOrderBy(0);
        $town->setName("test");
        $town->setBank(new Inventory());
        $town->setType($townClass);
        $town->setLanguage("fr");
        $town->setPopulation(10);
        $this->entityManager->persist($townClass);
        return $town;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function setUpCitizenProtos(): array
    {
        $job = new CitizenProfession();
        $job->setName("scout");
        $job->setLabel("scout");
        $job->setIcon("scout");
        $job->setHeroic(true);
        $job->setDescription("scout");

        $tent = (new CitizenHomePrototype())->setIcon("")->setLabel("")->setLevel(1)->setAp(1)->setDefense(1)
            ->setAllowSubUpgrades(true)->setTheftProtection(false);

        $cause = (new CauseOfDeath())->setIcon("")->setLabel("")->setDescription("")->setRef(1);
        $this->entityManager->persist($job);
        $this->entityManager->persist($tent);
        $this->entityManager->persist($cause);
        return array($job, $tent, $cause);
    }

    /**
     * @param int $k
     * @param mixed $job
     * @param Town $town
     * @param mixed $tent
     * @param mixed $cause
     * @return Citizen
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function setUpCitizen(int $k, mixed $job, Town $town, mixed $tent, mixed $cause): Citizen
    {
        $c = new Citizen();
        $u = new User();
        $u->setName("toto" . $k);
        $u->setEmail("tata" . $k);
        $u->setValidated(true);
        $c->setUser($u);
        $c->setProfession($job);
        $c->setInventory(new Inventory());
        $c->setTown($town);
        $home = new CitizenHome();
        $home->setChest(new Inventory());
        $home->setPrototype($tent);
        $c->setHome($home);
        $c->setCauseOfDeath($cause);
        $c->setBp(1);
        $c->setAp(1);
        $c->setPm(1);
        $this->entityManager->persist($u);
        return $c;
    }

}