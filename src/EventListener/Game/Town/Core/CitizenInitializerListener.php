<?php


namespace App\EventListener\Game\Town\Core;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\TeamTicket;
use App\Entity\TownSlotReservation;
use App\Entity\UserGroup;
use App\Event\Game\Town\Basic\Core\AfterJoinTownEvent;
use App\Event\Game\Town\Basic\Core\JoinTownEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\CrowService;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\PermissionHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: JoinTownEvent::class, method: 'createCitizen', priority: 0)]
#[AsEventListener(event: JoinTownEvent::class, method: 'handleTeamTicket', priority: 1)]
#[AsEventListener(event: AfterJoinTownEvent::class, method: 'handleGPS', priority: -1)]
final class CitizenInitializerListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            UserHandler::class,
            CitizenHandler::class,
            TownHandler::class,
            ItemFactory::class,
            PermissionHandler::class,
            GameProfilerService::class,
            InventoryHandler::class,
        ];
    }

    public function handleTeamTicket(JoinTownEvent $event): void {
        if (!$this->getService(UserHandler::class)->hasRole( $event->subject, 'ROLE_CROW' ))
            if (!$event->townConfig->get( TownConf::CONF_FEATURE_NO_TEAMS ) && $event->town->getLanguage() !== null && $event->town->getLanguage() !== 'multi' && $event->town->getRankingEntry() && !$event->town->getRankingEntry()->getEvent() && $event->town->getSeason())
                $this->getService(EntityManagerInterface::class)->persist(
                    (new TeamTicket())
                        ->setTown( $event->town->getRankingEntry() )
                        ->setSeason( $event->town->getSeason() )
                        ->setUser( $event->subject )
                        ->setTeam( $event->town->getLanguage() )
                );
    }

    public function createCitizen(JoinTownEvent $event): void {
        $home = new CitizenHome();
        $home
            ->setChest( $chest = new Inventory() )
            ->setPrototype( $this->getService(EntityManagerInterface::class)->getRepository( CitizenHomePrototype::class )->findOneBy(['level' => 0]) )
        ;

        $event->subject->addCitizen( $event->citizen = $citizen = new Citizen() );
        $citizen->setUser( $event->subject )
            ->setTown( $event->town )
            ->setInventory( new Inventory() )
            ->setHome( $home )
            ->setCauseOfDeath( $this->getService(EntityManagerInterface::class)->getRepository( CauseOfDeath::class )->findOneBy( ['ref' => CauseOfDeath::Unknown] ) )
            ->setHasSeenGazette( true );

        // Check for other coalition members
        foreach ($this->getService(UserHandler::class)->getAllOtherCoalitionMembers( $event->subject ) as $coa_member) {
            $coa_citizen = $coa_member->getCitizenFor($event->town);
            if ($coa_citizen) {
                $this->getService(EntityManagerInterface::class)->persist( $coa_citizen->setCoalized(true) );
                $citizen->setCoalized( true );
            }
        }

        (new Inventory())->setCitizen( $citizen );
        $this->getService(CitizenHandler::class)->inflictStatus( $citizen, 'clean' );
        foreach ($event->town->getCitizens() as $existing_citizen)
            if ($existing_citizen->getAlive() && $existing_citizen->hasStatus('tg_guitar')) {
                $this->getService(CitizenHandler::class)->inflictStatus($citizen, 'tg_guitar');
                break;
            }

        if ($this->getService(TownHandler::class)->getBuilding( $event->town, 'small_novlamps_#00' ))
            $this->getService(CitizenHandler::class)->inflictStatus( $citizen, 'tg_novlamps' );

        $base_profession = $this->getService(EntityManagerInterface::class)->getRepository(CitizenProfession::class)->findDefault();
        if (!$base_profession) throw new \Exception('Unable to find base profession.');

        $this->getService(CitizenHandler::class)->applyProfession( $citizen, $base_profession );

        $this->getService(InventoryHandler::class)->forceMoveItem( $chest, $this->getService(ItemFactory::class)->createItem( 'chest_citizen_#00' ) );
        $this->getService(InventoryHandler::class)->forceMoveItem( $chest, $this->getService(ItemFactory::class)->createItem( 'food_bag_#00' ) );

        // Adding default heroic action
        $heroic_actions = $this->getService(EntityManagerInterface::class)->getRepository(HeroicActionPrototype::class)->findBy(['unlockable' => false]);
        foreach ($heroic_actions as $heroic_action)
            /** @var $heroic_action HeroicActionPrototype */
            $citizen->addHeroicAction( $heroic_action );

        $town_group = $this->getService(EntityManagerInterface::class)->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $event->town->getId()] );
        if ($town_group) $this->getService(PermissionHandler::class)->associate( $event->subject, $town_group );

        $this->getService(EntityManagerInterface::class)->persist($citizen);
    }

    public function handleGPS(AfterJoinTownEvent $event): void
    {
        $this->getService(GameProfilerService::class)->recordCitizenJoined( $event->before->subject->getActiveCitizen(), $event->before->auto ? 'follow' : 'create' );
    }

}