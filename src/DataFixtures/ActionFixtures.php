<?php

namespace App\DataFixtures;

use App\Entity\AffectAP;
use App\Entity\AffectBlueprint;
use App\Entity\AffectCP;
use App\Entity\AffectDeath;
use App\Entity\AffectHome;
use App\Entity\AffectItemConsume;
use App\Entity\AffectItemSpawn;
use App\Entity\AffectMessage;
use App\Entity\AffectOriginalItem;
use App\Entity\AffectPicto;
use App\Entity\AffectPM;
use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\AffectStatus;
use App\Entity\AffectTown;
use App\Entity\AffectWell;
use App\Entity\AffectZombies;
use App\Entity\AffectZone;
use App\Entity\BuildingPrototype;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\EscortActionGroup;
use App\Entity\HeroicActionPrototype;
use App\Entity\HomeActionPrototype;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\PictoPrototype;
use App\Entity\RequireAP;
use App\Entity\RequireBuilding;
use App\Entity\RequireConf;
use App\Entity\RequireCounter;
use App\Entity\RequireCP;
use App\Entity\RequireDay;
use App\Entity\RequireEvent;
use App\Entity\RequireHome;
use App\Entity\RequireItem;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Entity\RequirePM;
use App\Entity\RequireStatus;
use App\Entity\RequireZombiePresence;
use App\Entity\RequireZone;
use App\Entity\Result;
use App\Entity\SpecialActionPrototype;
use App\Enum\ItemPoisonType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyHordes\Plugins\Fixtures\Action;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActionFixtures extends Fixture implements DependentFixtureInterface
{
    private EntityManagerInterface $entityManager;
    private Action $action_data;
    private array $action_data_cache = [];

    public function __construct(EntityManagerInterface $em, Action $action_data)
    {
        $this->entityManager = $em;
        $this->action_data = $action_data;
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $sub_cache
     * @param array|null $data
     * @return Requirement
     * @throws Exception
     */
    private function process_meta_requirement(        
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array &$sub_cache, ?array &$data = null): Requirement
    {
        if (!isset($cache[$id])) {
            if ($data === null && !isset($this->action_data_cache['meta_requirements'][$id])) throw new Exception('Requirement definition not found: ' . $id);

            $data = $data ?: $this->action_data_cache['meta_requirements'][$id];
            $requirement = $manager->getRepository(Requirement::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t<comment>Update</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new Requirement();
                $out->writeln( "\t\t<comment>Create</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->clear()
                ->setName( $id )
                ->setFailureMode( $data['type'] ?? Requirement::HideOnFail )
                ->setFailureText( isset($data['text_key']) ? $this->action_data_cache['message_keys'][$data['text_key']] : ($data['text'] ?? null) )
                ->setAtoms( $data['atomList'] ?? null );

            foreach (($data['collection'] ?? []) as $sub_id => $sub_req) {
                if (is_array($sub_req)) {
                    $sub_data = $sub_req;
                    $sub_req = "{$id}_i_{$sub_id}";
                }

                else {
                    if (!isset( $this->action_data_cache['requirements'][$sub_id] ))
                        throw new Exception('Requirement type definition not found: ' . $sub_id);
                    if (!isset( $this->action_data_cache['requirements'][$sub_id][$sub_req] ))
                        throw new Exception('Requirement entry definition not found: ' . $sub_id . '/' . $sub_req);

                    $sub_data = $this->action_data_cache['requirements'][$sub_id][$sub_req];
                }

                if (!isset($sub_cache[$sub_id])) $sub_cache[$sub_id] = [];
                                
                switch ($sub_id) {
                    case 'ap':
                        $requirement->setAp( $this->process_ap_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'status':
                        $requirement->setStatusRequirement( $this->process_status_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'item':
                        $requirement->setItem( $this->process_item_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'location':
                        $requirement->setLocation( $this->process_location_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'zombies':
                        $requirement->setZombies( $this->process_zombie_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'home':
                        $requirement->setHome( $this->process_home_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'counter':
                        $requirement->setCounter( $this->process_counter_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'building':
                        $requirement->setBuilding( $this->process_building_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'zone':
                        $requirement->setZone( $this->process_zone_requirement($manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'pm':
                        $requirement->setPm( $this->process_pm_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'cp':
                        $requirement->setCp( $this->process_cp_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'conf':
                        $requirement->setConf( $this->process_conf_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'day':
                        $requirement->setDay( $this->process_day_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'event':
                        $requirement->setEvent( $this->process_event_requirement( $manager, $out, $sub_cache[$sub_id], $sub_req, $sub_data ) );
                        break;
                    case 'custom':
                        $requirement->setCustom( $sub_data[0] );
                        break;
                    default:
                        throw new Exception('No handler for requirement type ' . $sub_id);
                }
            }

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t<comment>Skip</comment> meta condition <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireAP
     * @throws Exception
     */
    private function process_ap_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireAP
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireAP::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireAP();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequirePM
     * @throws Exception
     */
    private function process_pm_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequirePM
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequirePM::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequirePM();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireDay
     * @throws Exception
     */
    private function process_day_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireDay
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireDay::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireDay();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>day/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireEvent
     * @throws Exception
     */
    private function process_event_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireEvent
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireEvent::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>event/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireEvent();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>event/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $requirement->setName( $id )->setEventName( $data['name'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>event/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireCP
     * @throws Exception
     */
    private function process_cp_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireCP
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireCP::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireCP();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setMin( $data['min'] )->setMax( $data['max'] )->setRelativeMax( $data['relative'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireConf
     * @throws Exception
     */
    private function process_conf_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireConf
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireConf::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireConf();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )->setConf( $data['value'] )->setBoolVal( $data['bool'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>conf/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireStatus
     * @throws Exception
     */
    private function process_status_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out, 
        array &$cache, string $id, array $data): RequireStatus
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireStatus::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireStatus();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $status = isset($data['status']) ? $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['status']]) : null;
            $prof = isset($data['profession']) ? $manager->getRepository(CitizenProfession::class)->findOneBy(['name' => $data['profession']]) : null;
            $role = isset($data['role']) ? $manager->getRepository(CitizenRole::class)->findOneBy(['name' => $data['role']]) : null;
            if (isset($data['status']) && !$status) throw new Exception('Status condition not found: ' . $data['status']);
            if (isset($data['profession']) && !$prof) throw new Exception('Profession not found: ' . $data['profession']);
            if (isset($data['role']) && !$role) throw new Exception('Role not found: ' . $data['role']);

            $requirement->setName( $id )->setEnabled( $data['enabled'] ?? null )->setStatus( $status ?? null )->setProfession( $prof ?? null )->setRole( $role ?? null)->setBanished( $data['ban'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireItem
     * @throws Exception
     */
    private function process_item_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireItem
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireItem::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireItem();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $prototype = empty($data['item']) ? null : $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['item']]);
            if (!empty($data['item']) && ! $prototype) {
                throw new Exception('Item prototype not found: ' . $data['item']);
            }

            $property  = empty($data['prop']) ? null : $manager->getRepository(ItemProperty::class )->findOneBy(['name' => $data['prop']]);
            if (!empty($data['prop']) && ! $property)
                throw new Exception('Item property not found: ' . $data['prop']);

            if (!$prototype && !$property)
                throw new Exception('Item condition must have a prototype or property attached. not found: ' . $data['status']);

            $requirement->setName( $id )->setPrototype( $prototype )->setProperty( $property )->setCount( $data['count'] ?? 1 )->setAllowPoison( $data['allowPoison'] ?? false );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireLocation
     * @throws Exception
     */
    private function process_location_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireLocation
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireLocation::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireLocation();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id );
            if (count($data) === 1 && isset( $data[0] ))
                $requirement->setLocation( $data[0] );
            else $requirement
                ->setMinDistance( $data['min'] ?? null )
                ->setMaxDistance( $data['max'] ?? null )
                ->setLocation( $data['type'] ?? ( ( isset($data['min']) || isset($data['max']) ) ? RequireLocation::LocationOutsideOrExploring : RequireLocation::LocationInTown ) );

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>location/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireZombiePresence
     * @throws Exception
     */
    private function process_zombie_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireZombiePresence
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireZombiePresence::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireZombiePresence();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id )
                ->setNumber( $data['min'] )
                ->setMustBlock( $data['block'] ?? null )
                ->setTempControlAllowed( $data['temp'] ?? null );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>zombies/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireHome
     * @throws Exception
     */
    private function process_home_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireHome
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireHome::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireHome();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement
                ->setName( $id )
                ->setMinLevel( $data['min_level'] ?? null )
                ->setMaxLevel( $data['max_level'] ?? null );

            if (isset($data['upgrade'])) {
                $proto = $manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneBy(['name' => $data['upgrade']]);
                if (!$proto) throw new Exception('Home upgrade prototype not found: ' . $data['upgrade']);
                $requirement->setUpgrade( $proto );
            }

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireCounter
     * @throws Exception
     */
    private function process_counter_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireCounter
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireCounter::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireCounter();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement
                ->setName( $id )
                ->setType( $data['type'] )
                ->setMin( $data['min'] ?? null )
                ->setMax( $data['max'] ?? null );

            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>counter/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireZone
     * @throws Exception
     */
    private function process_zone_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireZone
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireZone::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireZone();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $requirement->setName( $id );
            if ($data['max_level']) $requirement->setMaxLevel( $data['max_level'] );
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return RequireBuilding
     * @throws Exception
     */
    private function process_building_requirement(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): RequireBuilding
    {
        if (!isset($cache[$id])) {
            $requirement = $manager->getRepository(RequireBuilding::class)->findOneBy(['name' => $id]);
            if ($requirement) $out->writeln( "\t\t\t<comment>Update</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $requirement = new RequireBuilding();
                $out->writeln( "\t\t\t<comment>Create</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $prototype = $manager->getRepository(BuildingPrototype::class)->findOneByName($data['prototype'], false );
            if (!$prototype)
                throw new Exception('Building prototype not found: ' . $data['item']);

            $requirement->setName( $id )->setBuilding( $prototype )
                ->setFound( $data['found'] ?? null )
                ->setComplete( $data['complete'] ?? null )
                ->setMinLevel( $data['minLevel'] ?? null )
                ->setMaxLevel( $data['maxLevel'] ?? null )
                ;
            $manager->persist( $cache[$id] = $requirement );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> condition <info>building/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }


    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $sub_cache
     * @param array|null $data
     * @return Result
     * @throws Exception
     */
    private function process_meta_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array &$sub_cache, ?array &$data = null): Result
    {
        if (!isset($cache[$id])) {
            if ($data === null && !isset($this->action_data_cache['meta_results'][$id])) throw new Exception('Result definition not found: ' . $id);
            $data = $data ?: $this->action_data_cache['meta_results'][$id];

            $result = $manager->getRepository(Result::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t<comment>Update</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new Result();
                $out->writeln( "\t\t<comment>Create</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->clear();

            $collection = isset($data['collection']) ? $data['collection'] : $data;
            foreach ($collection as $sub_id => $sub_res) {
                if (is_array($sub_res)) {
                    $sub_data = $sub_res;
                    $sub_res = "{$id}_i_{$sub_id}";
                } else {
                    if (!isset( $this->action_data_cache['results'][$sub_id] ))
                        throw new Exception('Result type definition not found: ' . $sub_id);
                    if (!isset( $this->action_data_cache['results'][$sub_id][$sub_res] ))
                        throw new Exception('Result entry definition not found: ' . $sub_id . '/' . $sub_res);

                    $sub_data = $this->action_data_cache['results'][$sub_id][$sub_res];
                }

                if (!isset($sub_cache[$sub_id])) $sub_cache[$sub_id] = [];

                switch ($sub_id) {
                    case 'status':
                        $result->setStatus( $this->process_status_effect($manager,$out,$sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'ap':
                        $result->setAp( $this->process_ap_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'bp':
                        $result->setBlueprint( $this->process_blueprint_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'pm':
                        $result->setPm( $this->process_pm_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'cp':
                        $result->setCp( $this->process_cp_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'death':
                        $result->setDeath( $this->process_death_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'item':
                        $result->setItem( $this->process_item_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'target':
                        $result->setTarget( $this->process_item_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'spawn':
                        $result->setSpawn( $this->process_spawn_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'consume':
                        $result->setConsume( $this->process_consume_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'zombies':
                        $result->setZombies( $this->process_zombie_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'home':
                        $result->setHome( $this->process_home_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'zone':
                        $result->setZone( $this->process_zone_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'well':
                        $result->setWell( $this->process_well_effect($manager, $out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'group':
                        $result->setResultGroup( $this->process_group_effect($manager, $out, $sub_cache[$sub_id], $cache, $sub_cache, $sub_res, $sub_data) );
                        break;
                    case 'rp':
                        $result->setRolePlayText( $sub_data[0] );
                        break;
                    case 'picto':
                        $result->setPicto( $this->process_picto_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'globalpicto':
                        $result->setGlobalPicto( $this->process_picto_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'message':
                        $result->setMessage( $this->process_message_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'town':
                        $result->setTown( $this->process_town_effect($manager,$out, $sub_cache[$sub_id], $sub_res, $sub_data) );
                        break;
                    case 'custom':
                        $result->setCustom( $sub_data[0] );
                        break;
                    default:
                        throw new Exception('No handler for effect type ' . $sub_id);
                }
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t<comment>Skip</comment> meta effect <info>$id</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }
    
    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectStatus
     * @throws Exception
     */
    private function process_status_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectStatus
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectStatus::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectStatus();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $status_from = empty($data['from']) ? null : $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['from']]);
            if (!$status_from && !empty($data['from'])) throw new Exception('Status effect not found: ' . $data['from']);
            $status_to = empty($data['to']) ? null : $manager->getRepository(CitizenStatus::class)->findOneBy(['name' => $data['to']]);
            if (!$status_to && !empty($data['to'])) throw new Exception('Status effect not found: ' . $data['to']);

            $role = (empty($data['role']) || !isset( $data['enabled'] ) || $data['enabled'] === null) ? null : $manager->getRepository(CitizenRole::class)->findOneBy(['name' => $data['role']]);

            $result
                ->setResetThirstCounter( $data['reset_thirst'] ?? null )
                ->setCitizenHunger( $data['hunger'] ?? null )
                ->setForced( $data['force'] ?? false )
                ->setCounter( $data['counter'] ?? null );

            if (!$status_from && !$status_to && !$result->getResetThirstCounter() && !$result->getCitizenHunger() && $result->getCounter() === null && $role === null) {
                throw new Exception('Status effects must have at least one attached status.');
            }

            $result->setName( $id )->setInitial( $status_from )->setResult( $status_to )->setRole($role)->setRoleAdd( $data['enabled'] ?? null);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>status/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectAP
     */
    private function process_ap_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectAP
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectAP::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectAP();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setAp( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>ap/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectPM
     */
    private function process_pm_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectPM
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectPM::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectPM();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setPm( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>pm/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectCP
     */
    private function process_cp_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectCP
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectCP::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectCP();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( $data['max'] )->setCp( $data['num'] );
            if ($data['max']) $result->setBonus( $data['num'] );
            else $result->setBonus( isset($data['bonus']) ? $data['bonus'] : 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>cp/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectDeath
     */
    private function process_death_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectDeath
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectDeath::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectDeath();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setCause(  $manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => $data[0]] ));
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>death/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectBlueprint
     * @throws Exception
     */
    private function process_blueprint_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectBlueprint
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectBlueprint::class)->findOneBy(['name' => $id]);
            if ($result) {
                $result->getList()->clear();
                $out->writeln( "\t\t\t<comment>Update</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            else {
                $result = new AffectBlueprint();
                $out->writeln("\t\t\t<comment>Create</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG);
            }

            $result->setName( $id );
            if (count($data) === 1 && is_numeric( $data[0] ))
                $result->setType( $data[0] );
            else {
                $result->setType( -1 );
                foreach ($data as $proto) {

                    $bpp = $manager->getRepository(BuildingPrototype::class)->findOneByName($proto, false );
                    if (!$bpp) throw new Exception("Building Prototype not found: {$proto}");

                    $result->addList( $bpp );
                }
            }


            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>blueprint/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectOriginalItem
     * @throws Exception
     */
    private function process_item_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectOriginalItem 
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectOriginalItem::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectOriginalItem();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }
            $morph_to = empty($data['morph']) ? null : $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['morph']]);
            if (!$morph_to && !empty($data['morph'])) throw new Exception('Item prototype not found: ' . $data['morph']);

            if ($morph_to && $data['consume']) throw new Exception('Item effects cannot morph and consume at the same time!');

            if (($data['poison'] ?? null) === true) $data['poison'] = ItemPoisonType::Deadly;
            if (($data['poison'] ?? null) === false) $data['poison'] = ItemPoisonType::None;
            $result->setName( $id )->setConsume( $data['consume'] )->setMorph( $morph_to )
                ->setBreak( $data['break'] ?? null )
                ->setPoison( $data['poison'] ?? null );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>item/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
        
        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectItemSpawn
     * @throws Exception
     */
    private function process_spawn_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectItemSpawn
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectItemSpawn::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectItemSpawn())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            if (isset($data['where']))
                $actual_data = $data['what'];
            else $actual_data = $data['what'] ?? $data;
            $target = $data['where'] ?? AffectItemSpawn::DropTargetDefault;

            if (count($actual_data) === 1) {
                $name = is_array($actual_data[0]) ? $actual_data[0][0] : $actual_data[0];
                $count =  is_array($actual_data[0]) ? $actual_data[0][1] : 1;
                $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
                if (!$prototype) throw new Exception('Item prototype not found: ' . $name);
                $result->setItemGroup(null)->setPrototype( $prototype )->setCount( $count )->setSpawnTarget($target);
            } else {
                $g_name = "efg_{$id}";
                $group = $manager->getRepository( ItemGroup::class )->findOneBy(['name' => $g_name]);
                if ($group) $group->getEntries()->clear();
                else $group = (new ItemGroup())->setName( $g_name );

                foreach ($actual_data as $entry) {
                    [$p,$c] = is_array($entry) ? $entry : [$entry,1];
                    $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $p]);

                    if (!$prototype) {
                        print_r($data);
                        throw new Exception('Item prototype not found4: ' . $p);
                    }
                    $group->addEntry( (new ItemGroupEntry())->setChance($c)->setPrototype( $prototype ) );
                }

                $result->setPrototype(null)->setItemGroup( $group )->setCount( 1 )->setSpawnTarget($target);
                $manager->persist( $group );
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>spawn/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectItemConsume
     * @throws Exception
     */
    private function process_consume_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectItemConsume
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectItemConsume::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectItemConsume())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            [$name,$count] = count($data) > 1 ? $data : [$data[0],1];
            $prototype = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
            if (!$prototype) throw new Exception('Item prototype not found: ' . $name);
            $result->setPrototype( $prototype )->setCount( $count );

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>consume/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectZombies
     */
    private function process_zombie_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectZombies
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectZombies::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectZombies();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setMax( isset($data['max']) ? $data['max'] : $data['num'] )->setMin( isset($data['min']) ? $data['min'] : $data['num'] );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>zombie/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectHome
     */
    private function process_home_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectHome
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectHome::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectHome();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setAdditionalDefense( $data['def'] ?? 0 )->setAdditionalStorage( $data['store'] ?? 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectWell
     */
    private function process_well_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectWell
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectWell::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectWell();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setFillMax( $data['max'] )->setFillMin( $data['min'] );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>well/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectZone
     */
    private function process_zone_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectZone
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectZone::class)->findOneBy(['name' => $id]);
            /** @var AffectZone $result */
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectZone();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $escape = is_array( $data['escape'] ?? null ) ? $data['escape'][1] : ($data['escape'] ?? null);
            $escape_tag = is_array( $data['escape'] ?? null ) ? $data['escape'][0] : null;

            $result->setName( $id )
                ->setUncoverZones( $data['scout'] ?? false )
                ->setUncoverRuin( $data['uncover'] ?? false )
                ->setEscape( $escape )
                ->setEscapeTag( $escape_tag )
                ->setImproveLevel( $data['improve'] ?? null )
                ->setChatSilence( $data['chatSilence'] ?? null);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>zone/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param array $meta_cache
     * @param array $sub_cache
     * @param string $id
     * @param array $data
     * @return AffectResultGroup
     * @throws Exception
     */
    private function process_group_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, array &$meta_cache, array &$sub_cache, string $id, array $data): AffectResultGroup
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectResultGroup::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = (new AffectResultGroup())->setName( $id );
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            foreach ( $result->getEntries() as $entry ) $manager->remove( $entry ); $result->getEntries()->clear();
            foreach ( $data as $k => $entry ) {

                $entry_obj = new AffectResultGroupEntry();
                $entry_obj->setCount( $entry[1] );

                if (!is_array($entry[0])) $entry[0] = [$entry[0]];
                foreach ( $entry[0] as $n => $nested_action ) {
                    if (is_array($nested_action))
                        $entry_obj->addResult($this->process_meta_effect($manager, $out, $meta_cache, "{$id}_{$k}_{$n}", $sub_cache, $nested_action));
                    else $entry_obj->addResult($this->process_meta_effect($manager, $out, $meta_cache, $nested_action, $sub_cache));
                }
                $result->addEntry( $entry_obj );
                $manager->persist( $entry_obj );
            }

            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>group/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectPicto
     */
    private function process_picto_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectPicto
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectPicto::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectPicto();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setPrototype(  $manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $data[0]]));
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>picto/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectMessage
     */
    private function process_message_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectMessage
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectMessage::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectMessage();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setText(isset($data['text_key']) ? $this->action_data_cache['message_keys'][$data['text_key']] : $data['text'])->setEscort($data['escort'] ?? null);
            if(isset($data['ordering']))
                  $result->setOrdering($data['ordering']);
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>message/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @param array $cache
     * @param string $id
     * @param array $data
     * @return AffectTown
     */
    private function process_town_effect(
        ObjectManager $manager, ConsoleOutputInterface $out,
        array &$cache, string $id, array $data): AffectTown
    {
        if (!isset($cache[$id])) {
            $result = $manager->getRepository(AffectTown::class)->findOneBy(['name' => $id]);
            if ($result) $out->writeln( "\t\t\t<comment>Update</comment> effect <info>town/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            else {
                $result = new AffectTown();
                $out->writeln( "\t\t\t<comment>Create</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );
            }

            $result->setName( $id )->setAdditionalDefense( $data['def'] ?? 0 );
            $manager->persist( $cache[$id] = $result );
        } else $out->writeln( "\t\t\t<comment>Skip</comment> effect <info>home/{$id}</info>", OutputInterface::VERBOSITY_DEBUG );

        return $cache[$id];
    }

    public function generate_action( ObjectManager $manager, ConsoleOutputInterface $out, string $action,
                                     array &$set_meta_requirements, array &$set_sub_requirements,
                                     array &$set_meta_results, array &$set_sub_results,
                                     array &$set_actions): ItemAction {

        if (!isset($set_actions[$action])) {
            if (!isset($this->action_data_cache['actions'][$action])) throw new Exception('Action definition not found: ' . $action);

            $data = $this->action_data_cache['actions'][$action];
            $new_action = $manager->getRepository(ItemAction::class)->findOneBy(['name' => $action]);
            if ($new_action) $out->writeln( "\t<comment>Update</comment> action <info>$action</info> ('<info>{$data['label']}</info>')", OutputInterface::VERBOSITY_DEBUG );
            else {
                $new_action = new ItemAction();
                $out->writeln( "\t<comment>Create</comment> action <info>$action</info> ('<info>{$data['label']}</info>')", OutputInterface::VERBOSITY_DEBUG );
            }

            $new_action->setName( $action )->setLabel( $data['label'] )->setRenderer( $data['renderer'] ?? null )->clearRequirements()->clearResults()
                ->setPriority( $data['priority'] ?? 0 )
                ->setMessage( isset($data['message_key']) ? $this->action_data_cache['message_keys'][$data['message_key']] : ($data['message'] ?? null) )
                ->setEscortMessage( isset($data['escort_message_key']) ? $this->action_data_cache['message_keys'][$data['escort_message_key']] : ($data['escort_message'] ?? null) )
                ->setConfirm( $data['confirm'] ?? false )
                ->setConfirmMsg( $data['confirmMsg'] ?? null );

            $new_action->setTooltip(isset($data['tooltip_key']) ? $this->action_data_cache['message_keys'][$data['tooltip_key']] : ($data["tooltip"] ?? null));

            if ($new_action->getTarget() && !isset($data['target'])) {
                $manager->remove( $new_action->getTarget() );
                $new_action->setTarget(null);
            }

            if (isset($data['target'])) {
                if (!$new_action->getTarget()) $new_action->setTarget( new ItemTargetDefinition() );
                $new_action->getTarget()
                    ->setSpawner( $data['target']['type'] ?? ItemTargetDefinition::ItemSelectionType )
                    ->setHeavy( $data['target']['heavy'] ?? null )
                    ->setPoison( $data['target']['poison'] ?? null )
                    ->setBroken( $data['target']['broken'] ?? null );
                if (isset( $data['target']['property'] )) {
                    $prop = $manager->getRepository(ItemProperty::class)->findOneBy(['name' => $data['target']['property']]);
                    if (!$prop) throw new Exception("Item property not found: '{$data['target']['property']}'");
                    $new_action->getTarget()->setTag($prop);
                } else $new_action->getTarget()->setTag(null);
                if (isset( $data['target']['prototype'] )) {
                    $proto = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $data['target']['prototype']]);
                    if (!$proto) throw new Exception("Item prototype not found: '{$data['target']['prototype']}'");
                    $new_action->getTarget()->setPrototype($proto);
                } else $new_action->getTarget()->setPrototype(null);
            }

            $new_action
                ->setAllowWhenTerrorized( $data['allow_when_terrorized'] ?? false )
                ->setKeepsCover( $data['cover'] ?? false )
                ->setAllowedAtGate( $data['at00'] ?? false )
                ->setPoisonHandler( $data['poison'] ?? ItemAction::PoisonHandlerIgnore );

            foreach ( $data['meta'] as $num => $requirement ) {
                if (is_array($requirement))
                    $new_action->addRequirement( $this->process_meta_requirement( $manager, $out, $set_meta_requirements, "{$action}_{$num}", $set_sub_requirements, $requirement ) );
                else $new_action->addRequirement( $this->process_meta_requirement( $manager, $out, $set_meta_requirements, $requirement, $set_sub_requirements ) );
            }

            foreach ( $data['result'] as $num => $result ) {
                if (is_array($result))
                    $new_action->addResult( $this->process_meta_effect($manager,$out, $set_meta_results, "{$action}_{$num}", $set_sub_results, $result) );
                else $new_action->addResult( $this->process_meta_effect($manager,$out, $set_meta_results, $result, $set_sub_results) );
            }

            $manager->persist( $set_actions[$action] = $new_action );
        } else $out->writeln( "\t<comment>Skip</comment> action <info>$action</info> ('<info>{$set_actions[$action]->getLabel()}</info>')", OutputInterface::VERBOSITY_DEBUG );

        return $set_actions[$action];
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    public function insert_item_actions(ObjectManager $manager, ConsoleOutputInterface $out) {

        $this->action_data_cache = $this->action_data->data();
        $out->writeln( '<comment>Compiling item action fixtures.</comment>', OutputInterface::VERBOSITY_DEBUG );

        $set_meta_requirements = [];
        $set_sub_requirements = [];

        $set_meta_results = [];
        $set_sub_results = [];

        $set_actions = [];

        foreach ($this->action_data_cache['items'] as $item_name => $actions) {

            $item = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $item_name]);
            if (!$item) throw new Exception('Item prototype not found: ' . $item_name);

            $item->getActions()->clear();
            $out->writeln( "Compiling action set for item <info>{$item->getLabel()}</info>...", OutputInterface::VERBOSITY_DEBUG );

            foreach ($actions as $action)
                $item->addAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $item );
        }

        foreach ($this->action_data_cache['heroics'] as $action) {

            $action_proto = $manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $action['name']]);
            if (!$action_proto) $action_proto = (new HeroicActionPrototype)->setName( $action['name'] );
            $action_proto->setUnlockable($action['unlockable'])->setUsedMessage( $action['used'] ?? null );

            $out->writeln( "Compiling action set for heroic action <info>{$action['name']}</info>...", OutputInterface::VERBOSITY_DEBUG);

            $action_proto->setAction( $compiled_action = $this->generate_action( $manager, $out, $action['name'], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );
            $manager->persist( $action_proto );

            $sp_action_proto_name = "_hprx_{$action['name']}";
            $sp_action_proto = $manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => $sp_action_proto_name]);
            if (!$sp_action_proto) $sp_action_proto = (new SpecialActionPrototype)->setName( $sp_action_proto_name );
            $sp_action_proto
                ->setIcon('hero')
                ->setConsumable(true)
                ->setProxyFor( $action_proto );

            $out->writeln( "Creating proxy special action for herioic action <info>{$action['name']}</info>...", OutputInterface::VERBOSITY_DEBUG);
            $sp_action_proto->setAction( $compiled_action );

            $manager->persist( $sp_action_proto );
        }

        foreach ($this->action_data_cache['specials'] as $action) {
            $action_proto = $manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => $action['name']]);
            if (!$action_proto) $action_proto = (new SpecialActionPrototype)->setName( $action['name'] );
            $action_proto
                ->setIcon($action['icon'])
                ->setConsumable($action['consumable'] ?? true);

            $out->writeln( "Compiling action set for special action <info>{$action['name']}</info>...", OutputInterface::VERBOSITY_DEBUG);
            $action_proto->setAction( $this->generate_action( $manager, $out, $action['name'], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }

        foreach ($this->action_data_cache['camping'] as $action) {

            $action_proto = $manager->getRepository(CampingActionPrototype::class)->findOneBy(['name' => $action]);
            if (!$action_proto) $action_proto = (new CampingActionPrototype)->setName( $action );

            $action_proto->setAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $action_proto );
        }
        foreach ($manager->getRepository(CampingActionPrototype::class)->findAll() as $existing_action)
            if (!in_array( $existing_action->getName(), $this->action_data_cache['camping'] )) {
                $out->writeln("Removing obsolete camping action <info>{$existing_action->getName()}</info>.");
                $manager->remove( $existing_action );
            }

        $action_cache = [];
        foreach ($this->action_data_cache['home'] as $action_group) {

            $action_proto = $manager->getRepository(HomeActionPrototype::class)->findOneBy(['name' => $action_group[0]]);
            if (!$action_proto) $action_proto = (new HomeActionPrototype)->setName( $action_group[0] );
            $action_proto->setIcon( $action_group[1] );

            $action_proto->setAction( $this->generate_action( $manager, $out, $action_group[0], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $action_cache[] = $action_group[0];
            $manager->persist( $action_proto );
        }

        foreach ($manager->getRepository(HomeActionPrototype::class)->findAll() as $hap)
            if (!in_array($hap->getName(),$action_cache))
                $this->entityManager->remove( $hap );

        foreach ($this->action_data_cache['escort'] as $escort_key => $escort_group) {

            $escort_proto = $manager->getRepository(EscortActionGroup::class)->findOneBy(['name' => $escort_key]);
            if (!$escort_proto) $escort_proto = (new EscortActionGroup);
            $escort_proto
                ->setName( $escort_key )
                ->setIcon( $escort_group['icon'] )
                ->setLabel( $escort_group['label'] )
                ->setTooltip( $escort_group['tooltip'] ?? null )
                ->getActions()->clear();

            foreach ($escort_group['actions'] as $action_id)
                if (isset($set_actions[$action_id]))
                    $escort_proto->addAction( $set_actions[$action_id] );

            $manager->persist( $escort_proto );
        }

        /** @var ItemPrototype[] $all_prototypes */
        $all_prototypes = $this->entityManager->getRepository(ItemPrototype::class)->findAll();
        foreach ($all_prototypes as $prototype) {

            if ($prototype->getWatchpoint() !== 0 && !isset($this->action_data_cache['items_nw'][$prototype->getName()]))
                throw new Exception("Item prototype '{$prototype->getName()}' ({$prototype->getLabel()}) has {$prototype->getWatchpoint()} watch points, but no night watch action!");
            else if (isset($this->action_data_cache['items_nw'][$prototype->getName()])) {
                $prototype->setNightWatchAction( $this->generate_action( $manager, $out, $this->action_data_cache['items_nw'][$prototype->getName()], $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );
                $this->entityManager->persist($prototype);
            }
        }

        foreach ($this->action_data_cache['items'] as $item_name => $actions) {

            $item = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $item_name]);
            if (!$item) throw new Exception('Item prototype not found: ' . $item_name);

            $item->getActions()->clear();
            $out->writeln( "Compiling action set for item <info>{$item->getLabel()}</info>...", OutputInterface::VERBOSITY_DEBUG );

            foreach ($actions as $action)
                $item->addAction( $this->generate_action( $manager, $out, $action, $set_meta_requirements, $set_sub_requirements, $set_meta_results, $set_sub_results, $set_actions ) );

            $manager->persist( $item );
        }

        $manager->flush();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Actions</info>' );
        $output->writeln("");

        $this->insert_item_actions( $manager, $output );

        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [ ItemFixtures::class, RecipeFixtures::class, CitizenFixtures::class ];
    }
}
