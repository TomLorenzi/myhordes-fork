<?php

namespace App\DataFixtures;

use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ComplaintReason;
use App\Entity\HelpNotificationMarker;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Translation\T;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyHordes\Plugins\Fixtures\CitizenComplaint;
use MyHordes\Plugins\Fixtures\CitizenDeath;
use MyHordes\Plugins\Fixtures\CitizenHomeLevel;
use MyHordes\Plugins\Fixtures\CitizenHomeUpgrade;
use MyHordes\Plugins\Fixtures\CitizenNotificationMarker;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\CitizenStatus as CitizenStatusFixtures;
use MyHordes\Plugins\Fixtures\CitizenProfession as CitizenProfessionFixtures;
use MyHordes\Plugins\Fixtures\CitizenRole as CitizenRoleFixtures;

class CitizenFixtures extends Fixture implements DependentFixtureInterface
{

    private CitizenProfessionFixtures $profession_data;
    private CitizenStatusFixtures $citizen_status;
    private CitizenDeath $causes_of_death;
    private CitizenHomeLevel $home_levels;
    private CitizenHomeUpgrade $home_upgrades;
    private CitizenRoleFixtures $role_data;
    private CitizenNotificationMarker $notificationMarkers;
    private CitizenComplaint $complaintReasons;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em,
        CitizenProfessionFixtures $citizen_profession,
        CitizenStatusFixtures $citizen_status,
        CitizenDeath $citizen_death,
        CitizenHomeLevel $citizen_home_level,
        CitizenHomeUpgrade $citizen_home_upgrade,
        CitizenRoleFixtures $citizen_role,
        CitizenNotificationMarker $citizen_notifs,
        CitizenComplaint $citizen_complaint
    )
    {
        $this->entityManager = $em;
        $this->profession_data = $citizen_profession;
        $this->citizen_status = $citizen_status;
        $this->role_data = $citizen_role;
        $this->causes_of_death = $citizen_death;
        $this->home_levels = $citizen_home_level;
        $this->home_upgrades = $citizen_home_upgrade;
        $this->notificationMarkers = $citizen_notifs;
        $this->complaintReasons = $citizen_complaint;
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_professions(ObjectManager $manager, ConsoleOutputInterface $out) {
        $profession_data = $this->profession_data->data();
        $out->writeln( '<comment>Citizen professions: ' . count($profession_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($profession_data) );

        // Iterate over all entries
        foreach ($profession_data as $entry) {
            // Get existing entry, or create new one
            /** @var CitizenProfession $entity */
            $entity = $this->entityManager->getRepository(CitizenProfession::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new CitizenProfession();
            else {
                $entity->getProfessionItems()->clear();
                $entity->getAltProfessionItems()->clear();
            }

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setHeroic( $entry['hero'] )
                ->setDescription( $entry['desc'])
                ->setPictoName( $entry['picto'] ?? null )
                ->setNightwatchDefenseBonus($entry['nightwatch_def_bonus'] ?? 0)
                ->setNightwatchSurvivalBonus($entry['nightwatch_surv_bonus'] ?? 0)
				->setDigBonus( $entry['dig_bonus'] ?? 0 );

            foreach ( $entry['items'] as $p_item ) {
                $i = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $p_item] );
                if (!$i) throw new Exception('Item prototype not found: ' . $p_item);
                $entity->addProfessionItem($i);
            }

            foreach ( $entry['items_alt'] as $p_item ) {
                $i = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $p_item] );
                if (!$i) throw new Exception('Alt Item prototype not found: ' . $p_item);
                $entity->addAltProfessionItem($i);
            }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_status_types(ObjectManager $manager, ConsoleOutputInterface $out) {
        $citizen_status = $this->citizen_status->data();
        $out->writeln( '<comment>Status: ' . count($citizen_status) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($citizen_status) );

        // Iterate over all entries
        foreach ($citizen_status as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenStatus::class)->findOneBy( ['name' =>  $entry['name']] );
            if ($entity === null) $entity = new CitizenStatus();

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] ?? $entry['name'] )
                ->setIcon( $entry['icon'] ?? $entry['name'] )
                ->setHidden( !isset($entry['label']) )
                ->setDescription($entry['description'] ?? null)
                ->setNightWatchDefenseBonus( $entry['nw_def'] ?? 0 )
                ->setNightWatchDeathChancePenalty( $entry['nw_death'] ?? 0.0 )
				->setVolatile($entry['volatile'] ?? true);

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_home_prototypes(ObjectManager $manager, ConsoleOutputInterface $out)
    {
        $home_levels = $this->home_levels->data();
        $out->writeln('<comment>Home Prototypes: ' . count($home_levels) . ' fixture entries available.</comment>');

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($home_levels) );

        // Iterate over all entries
        foreach ($home_levels as $level => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenHomePrototype::class)->findOneBy( ['level' => $level] );
            if ($entity === null) $entity = new CitizenHomePrototype();

            $entity->setLevel($level)
                ->setAp( $entry['ap'] )
                ->setApUrbanism( $entry['ap_urbanism'] ?? 0 )
                ->setIcon( $entry['icon'] )
                ->setAllowSubUpgrades( $entry['upgrades'] )
                ->setDefense( $entry['def'] )
                ->setLabel( $entry['label'] )
                ->setTheftProtection( $entry['theft'] );

            $building = empty($entry['building']) ? null : $manager->getRepository(BuildingPrototype::class)->findOneByName( $entry['building'], false );
            if (!empty($building) && !$building) throw new Exception("Unable to locate building prototype '{$entry['building']}'");
            $entity->setRequiredBuilding( $building );

            if (empty($entry['resources'])) {
                if ($entity->getResources()) {
                    $manager->remove( $entity->getResources() );
                    $entity->setResources( null );
                }
            } else {

                if ($entity->getResources()) $entity->getResources()->getEntries()->clear();
                else $entity->setResources( (new ItemGroup())->setName( "hu_{$level}_res" ) );

                foreach ( $entry['resources'] as $item => $count ) {

                    $ip = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item] );
                    if (!$item) throw new Exception("Unable to locate item prototype '{$item}'");
                    $entity->getResources()->addEntry( (new ItemGroupEntry())->setPrototype( $ip )->setChance( $count ) );

                }

            }

            if (empty($entry['resources_urbanism'])) {
                if ($entity->getResourcesUrbanism()) {
                    $manager->remove( $entity->getResourcesUrbanism() );
                    $entity->setResourcesUrbanism( null );
                }
            } else {

                if ($entity->getResourcesUrbanism()) $entity->getResourcesUrbanism()->getEntries()->clear();
                else $entity->setResourcesUrbanism( (new ItemGroup())->setName( "hu_{$level}_ures" ) );

                foreach ( $entry['resources_urbanism'] as $item => $count ) {

                    $ip = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item] );
                    if (!$item) throw new Exception("Unable to locate item prototype '{$item}'");
                    $entity->getResourcesUrbanism()->addEntry( (new ItemGroupEntry())->setPrototype( $ip )->setChance( $count ) );

                }

            }
            // Persist
            $manager->persist($entity);

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_home_upgrades(ObjectManager $manager, ConsoleOutputInterface $out)
    {
        $home_upgrades = $this->home_upgrades->data();
        $out->writeln('<comment>Home Upgrades: ' . count($home_upgrades) . ' fixture entries available.</comment>');

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($home_upgrades) );

        // Iterate over all entries
        foreach ($home_upgrades as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CitizenHomeUpgradePrototype::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new CitizenHomeUpgradePrototype();

            $entity->setName( $entry['name'] )->setLabel( $entry['label'] )->setDescription( $entry['desc'] )
                ->setIcon( $entry['icon'] ?? $entry['name'] );

            // Persist & flush
            $manager->persist($entity);
            $manager->flush();

            // Refresh
            $entity = $this->entityManager->getRepository(CitizenHomeUpgradePrototype::class)->findOneBy( ['name' => $entry['name']] );

            foreach ( $entry['levels'] as $level => $res ) {
                $lv_entry = $manager->getRepository(CitizenHomeUpgradeCosts::class)->findOneByPrototype( $entity, $level );
                if (!$lv_entry) $lv_entry = (new CitizenHomeUpgradeCosts())->setPrototype($entity)->setLevel( $level );

                $lv_entry->setAp( $res[0] );
                if (empty($res[1])) {
                    if ($lv_entry->getResources()) {
                        $manager->remove( $lv_entry->getResources() );
                        $lv_entry->setResources( null );
                    }
                } else {

                    if ($lv_entry->getResources()) $lv_entry->getResources()->getEntries()->clear();
                    else $lv_entry->setResources( (new ItemGroup())->setName( "hu_{$entry['name']}_{$level}_res" ) );

                    foreach ( $res[1] as $item => $count ) {

                        $ip = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item] );
                        if (!$item) throw new Exception("Unable to locate item prototype '{$item}'");
                        $lv_entry->getResources()->addEntry( (new ItemGroupEntry())->setPrototype( $ip )->setChance( $count ) );

                    }
                }

                $manager->persist( $lv_entry );
            }

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_cod(ObjectManager $manager, ConsoleOutputInterface $out) {
        $causes_of_death = $this->causes_of_death->data();
        $out->writeln( '<comment>Causes of Death: ' . count($causes_of_death) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($causes_of_death) );

        // Iterate over all entries
        foreach ($causes_of_death as $entry) {
            // Get existing entry, or create new one
            /** @var CauseOfDeath $entity */
            $entity = $this->entityManager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => $entry['ref']] );
            if ($entity === null) $entity = (new CauseOfDeath())->setRef( $entry['ref'] );

            $entity->getPictos()->clear();
            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setDescription( $entry['desc'] );

            if (isset($entry['pictos'])) {
                foreach ($entry['pictos'] as $picto) {
                    $proto = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $picto]);
                    if (!$proto) throw new Exception("Unable to locate picto prototype '{$picto}'");

                    $entity->addPicto($proto);
                }
            }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    protected function insert_roles(ObjectManager $manager, ConsoleOutputInterface $out) {
        $role_data = $this->role_data->data();
        $out->writeln( '<comment>Citizen roles: ' . count($role_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($role_data) );

        // Iterate over all entries
        foreach ($role_data as $entry) {
            // Get existing entry, or create new one
            /** @var CitizenRole $entity */
            $entity = $this->entityManager->getRepository(CitizenRole::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new CitizenRole();

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setVotable( $entry['vote'] )
                ->setHidden( $entry['hidden'] )
                ->setSecret( $entry['secret'] )
                ->setMessage( $entry['message'] ?? null)
                ->setHelpSection($entry['help_section'] ?? null);

            T::__("Du bist der {rolename} dieser Stadt.", "game");

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_hnm(ObjectManager $manager, ConsoleOutputInterface $out) {
        $notificationMarkers = $this->notificationMarkers->data();
        $out->writeln( '<comment>Help notification markers: ' . count($notificationMarkers) . ' fixture entries available.</comment>' );

        // Iterate over all entries
        foreach ($notificationMarkers as $entry) {

            if (!$manager->getRepository(HelpNotificationMarker::class)->findOneBy(['name' => $entry]))
                $manager->persist( (new HelpNotificationMarker())->setName( $entry ) );

        }

        $manager->flush();
    }

    protected function insert_complaint_reasons(ObjectManager $manager, ConsoleOutputInterface $out) {
        $complaintReasons = $this->complaintReasons->data();
        $out->writeln( '<comment>Complaint reasons: ' . count($complaintReasons) . ' fixture entries available.</comment>' );

        // Iterate over all entries
        foreach ($complaintReasons as $entry) {
            /** @var ComplaintReason $reason */
            $reason = $manager->getRepository(ComplaintReason::class)->findOneBy(['name' => $entry]);

            if (!$reason)
                $reason = (new ComplaintReason())->setName( $entry['name'] );
            
            $reason->setText($entry['text']);
            $manager->persist($reason);

        }

        $manager->flush();
    }


    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();

        try {
            $output->writeln( '<info>Installing fixtures: Citizen Database</info>' );
            $output->writeln("");

            $this->insert_professions( $manager, $output );
            $output->writeln("");
            $this->insert_status_types( $manager, $output );
            $output->writeln("");

            $this->insert_home_prototypes($manager, $output);
            $output->writeln("");
            $this->insert_home_upgrades($manager, $output);
            $output->writeln("");
            $this->insert_roles($manager, $output);
            $output->writeln("");

            $this->insert_cod($manager, $output);
            $output->writeln("");
            $this->insert_hnm($manager, $output);
            $output->writeln("");
            $this->insert_complaint_reasons($manager, $output);

        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

    }

    public function getDependencies(): array
    {
        return [ RecipeFixtures::class ];
    }
}
