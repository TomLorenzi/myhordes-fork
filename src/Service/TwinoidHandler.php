<?php


namespace App\Service;



use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\FoundRolePlayText;
use App\Entity\Picto;
use App\Entity\RolePlayText;
use App\Entity\Season;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Structures\MyHordesConf;
use App\Structures\TwinoidPayload;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwinoidHandler
{

    private MyHordesConf $conf;
    private UrlGeneratorInterface $generator;
    private EntityManagerInterface $em;
    private RandomGenerator $rand;
    private UserHandler $userHandler;

    public function __construct( ConfMaster $confMaster, UrlGeneratorInterface $generator, EntityManagerInterface $em, RandomGenerator $rand, UserHandler $uh) {
        $this->conf = $confMaster->getGlobalConf();
        $this->generator = $generator;
        $this->em = $em;
        $this->rand = $rand;
        $this->userHandler = $uh;
    }

    private function getScopeLanguage( string $scope ): ?string {
        //['www.hordes.fr','www.die2nite.com','www.dieverdammten.de','www.zombinoia.com']
        switch ($scope) {
            case 'www.hordes.fr':        return 'fr';
            case 'www.die2nite.com':     return 'en';
            case 'www.dieverdammten.de': return 'de';
            case 'www.zombinoia.com':    return 'es';
            default: return null;
        }
    }

    function clearImportedData( User $user, ?string $scope, bool $isPrimary ) {
        $lang = $scope === null ? null : $this->getScopeLanguage($scope);

        // Remove towns
        foreach ($user->getPastLifes() as $past) {
            if ($past->getTown()->getImported() && ($scope === null || $past->getTown()->getLanguage() === $lang)) {
                $user->removePastLife($past);
                $this->em->remove( $past );
            }
        }

        if ($isPrimary) $this->clearPrimaryImportedData($user);
    }

    function clearPrimaryImportedData( User $user ) {
        // Remove pictos
        foreach ($this->em->getRepository(Picto::class)->findBy(['imported' => true, 'user' => $user]) as $picto) {
            $user->removePicto($picto);
            $this->em->remove( $picto );
        }

        // Remove unlocked RP texts
        foreach ($this->em->getRepository(FoundRolePlayText::class)->findBy(['imported' => true, 'user' => $user]) as $rp) {
            $user->removeFoundText( $rp );
            $this->em->remove( $rp );
        }

        $user->setImportedSoulPoints( 0 );
        $user->setImportedHeroDaysSpent( 0 );
    }

    function importData( User $user, string $scope, TwinoidPayload $data, bool $isPrimary, bool $isLimited, bool $forceResetDisableFlag = false ): bool {
        if (($lang = $this->getScopeLanguage($scope)) === null) return false;

        //<editor-fold desc="Town Import">
        // Get existing towns
        $tid_list = [];
        foreach ($data->getPastTowns() as $town) $tid_list[$town->getID()] = false;

        foreach ($user->getPastLifes() as $past) {
            if ($past->getTown()->getImported() && $past->getTown()->getLanguage() === $lang ) {

                // The town is not in the list of imported towns; remove citizen
                if (!isset($tid_list[$past->getImportID()])) {
                    $user->removePastLife($past);
                    $this->em->remove( $past );
                } else
                    $tid_list[$past->getTown()->getBaseID()] = true;
            }
        }

        $default_town_type = $this->em->getRepository(TownClass::class)->findOneBy(['name' => TownClass::DEFAULT]);

        $seasons = [];
        foreach ($data->getPastTowns() as $town) if ($tid_list[$town->getID()] === false) {
            if (!isset($seasons[$town->getSeason()])) {
                $seasons[$town->getSeason()] = $this->em->getRepository(Season::class)->findOneBy(['number' => 0, 'subNumber' => $town->getSeason()]);
                if ($seasons[$town->getSeason()] === null) {
                    $seasons[$town->getSeason()] = (new Season())
                        ->setNumber(0)->setSubNumber($town->getSeason());
                    $this->em->persist( $seasons[$town->getSeason()] );
                }
            }

            $entry = $this->em->getRepository(TownRankingProxy::class)->findOneBy( ['imported' => true, 'baseID' => $town->getID(), 'language' => $lang] );
            if ($entry === null)
                $entry = (new TownRankingProxy())
                    ->setName( $town->getName() )
                    ->setBaseID( $town->getID() )
                    ->setImported( true )
                    ->setLanguage( $lang )
                    ->setType( $default_town_type )
                    ->setSeason( $seasons[$town->getSeason()] )
                    ->setDays( $town->getDay() )
                    ->setPopulation( 40 )
                    ->setV1($town->isOld());
            else $entry->setDays( max( $entry->getDays(), $town->getDay() ) );

            $proxy = (new CitizenRankingProxy())
                ->setBaseID( $user->getId() )
                ->setImportID( $town->getID() )
                ->setImportLang( $lang )
                ->setUser( $user )
                ->setCod( $town->convertDeath() )
                ->setComment( $town->getComment() )
                ->setLastWords( $town->getMessage() )
                ->setDay( $town->getSurvivedDays() )
                ->setConfirmed( true )
                ->setPoints( $town->getScore() )
                ->setLimitedImport( $isPrimary && $isLimited )
                ->setCleanupUsername($town->getCleanup()['user'])
                ->setCleanupType($town->getCleanup()['type']);

            if ($isPrimary && $isLimited) {
                $proxy->addDisableFlag(CitizenRankingProxy::DISABLE_ALL);
            }

            $entry->addCitizen(
                $proxy
            );

            $this->em->persist( $entry );
        } else {
            /** @var CitizenRankingProxy $entry */
            $entry = $this->em->getRepository(CitizenRankingProxy::class)->findOneBy( ['user' => $user, 'importID' => $town->getID(), 'importLang' => $lang] );
            if ($entry) {

                $entry
                    ->setComment( $town->getComment() )->setLastWords( $town->getMessage() )->setDay( $town->getSurvivedDays() )->setPoints( $town->getScore() )->setCod( $town->convertDeath() )->setCleanupUsername($town->getCleanup()['user'])
                    ->setCleanupType($town->getCleanup()['type'])
                    ->getTown()->setV1($town->isOld());

                if (($entry->getLimitedImport() && !($isPrimary && $isLimited)) || $forceResetDisableFlag)
                    $entry->setDisableFlag(CitizenRankingProxy::DISABLE_NOTHING);
                $this->em->persist( $entry );
            }
        }
        //</editor-fold>

        if ($isPrimary && !$isLimited) {
            //<editor-fold desc="Picto Import">

            $rps = 0;

            // Get existing pictos
            $pid_list = [];
            foreach ($data->getPictos() as $picto)
                if ($picto->convertPicto()) {
                    $pid_list[$picto->convertPicto()->getID()] = true;
                    if ($picto->convertPicto()->getName() === 'r_rp_#00')
                        $rps += $picto->getCount();
                }

            foreach ($this->em->getRepository(Picto::class)->findBy(['imported' => true, 'user' => $user]) as $picto) {

                // The picto is not in the list of imported pictos; remove it
                if (!isset($pid_list[$picto->getPrototype()->getId()])) {
                    $user->removePicto($picto);
                    $this->em->remove($picto);
                }
            }

            $already_persisted = [];
            $fun_unlock_feature = function ($feature) use ($user,&$already_persisted) {
                if (in_array($feature,$already_persisted)) return;
                $f = $this->em->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => $feature]);
                if (!$f) return;

                $e = $this->em->getRepository(FeatureUnlock::class)->findBy([
                    'user' => $user, 'expirationMode' => FeatureUnlock::FeatureExpirationNone, 'prototype' => $f
                ]);
                $already_persisted[] = $feature;
                if (empty($e)) $this->em->persist( (new FeatureUnlock)->setUser($user)->setPrototype($f)->setExpirationMode(FeatureUnlock::FeatureExpirationNone) );
            };

            foreach ($data->getPictos() as $picto) if ($picto->convertPicto()) {

                $entry = $this->em->getRepository(Picto::class)->findOneBy( ['imported' => true, 'user' => $user, 'prototype' => $picto->convertPicto()] );
                if ($entry === null) {
                    $entry = (new Picto())
                        ->setUser($user)
                        ->setImported(true)
                        ->setPersisted(2)
                        ->setPrototype($picto->convertPicto());
                }

                if ($entry->getPrototype()->getName() === 'r_ginfec_#00') $fun_unlock_feature('f_wtns');
                if ($entry->getPrototype()->getName() === 'r_armag_#00') $fun_unlock_feature('f_arma');

                $entry->setCount($picto->getCount());
                $this->em->persist( $entry );
            }
            //</editor-fold>

            $f_cam = $this->em->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_cam']);
            if ($this->em->getRepository(Season::class)->findLatest() === null && !$this->userHandler->checkFeatureUnlock($user, $f_cam, false))
                $this->em->persist( (new FeatureUnlock())->setPrototype( $f_cam )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationSeason)->setSeason( null ) );

            $existing_rps    = $this->em->getRepository(FoundRolePlayText::class)->findBy(['imported' => true,  'user' => $user]);
            if (count($existing_rps) < $rps) {
                // We need to unlock new RPs
                $existing_mh_all = $this->em->getRepository(FoundRolePlayText::class)->findBy(['user' => $user]);

                $all_texts = $this->em->getRepository(RolePlayText::class)->findAllByLang($lang);
                foreach ($existing_mh_all as $existing_rp)
                    /** @var $existing_rp FoundRolePlayText */
                    if (($key = array_search( $existing_rp->getText(), $all_texts )) !== false)
                        unset($all_texts[$key]);

                $picks = $this->rand->pick( $all_texts, $rps - count($existing_rps), true );
                foreach ($picks as $pick)
                    $user->getFoundTexts()->add((new FoundRolePlayText())
                        ->setUser($user)
                        ->setText($pick)
                        ->setImported(true));

            } elseif (count($existing_rps) > $rps) {
                // We need to remove RPs
                $picks = $this->rand->pick( $existing_rps, count($existing_rps) - $rps, true );
                foreach ($picks as $pick) {
                    $user->removeFoundText( $pick );
                    $this->em->remove( $pick );
                }
            }

            $user->setImportedHeroDaysSpent( $data->getSummaryHeroDays() );
            $this->em->persist($user);
        }

        return true;
    }
}