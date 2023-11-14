<?php

namespace App\Service\Actions\Ghost;

use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\TownHandler;
use App\Structures\EventConf;
use App\Structures\TownSetup;
use App\Traits\Actions\ActionResults\Citizen as RCitizen;
use App\Traits\Actions\ActionResults\CitizenResult;
use App\Traits\Actions\ActionResults\ErrorCode;
use App\Traits\Actions\ActionResults\Optional;
use App\Traits\Actions\ActionResults\Town as RTown;
use App\Traits\Actions\ActionResults\ErrorCode as RError;
use App\Traits\Actions\ActionResults\TownResult;
use App\Traits\Actions\FallibleAction;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CreateTownFromConfigAction
{
    use FallibleAction;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConfMaster $conf,
        private readonly GameFactory $gameFactory,
        private readonly TownHandler $townHandler,
        private readonly GameProfilerService $profiler
    ) { }

    /**
     * @param array $header
     * @param array $rules
     * @param User|null $creator
     * @param array|null $userSlots
     * @return RTown|RCitizen|RError
     * @noinspection PhpDocSignatureInspection
     */
    public function __invoke(
        array $header, array $rules,
        ?User $creator = null,
        ?array $userSlots = [],
        bool $force_disable_incarnate = false
    ): object
    {
        $seed = $header['townSeed'] ?? -1;

        if ($header['event'] ?? null) {
            $current_events = $header['event'] === 'none' ? [] : [ $this->conf->getEvent( $header['event'] ) ];
        } else $current_events = $this->conf->getCurrentEvents();

        $name_changers = array_values(
            array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
        );

        $town = $this->gameFactory->createTown(new TownSetup( $header['townType'],
            name:           $header['townName'] ?? null,
            language:       $header['townLang'] ?? 'multi',
            nameLanguage:   $header['townNameLang'] ?? null,
            typeDeriveFrom: $header['townBase'] ?? null,
            customConf:     $rules,
            seed:           $seed,
            nameMutator:    $name_changers[0] ?? null
                                         ));

        $town->setCreator($creator);
        if(!empty($header['townCode'])) $town->setPassword($header['townCode']);
        if ($header['event'] ?? null) $town->setManagedEvents( true );

        foreach ($userSlots as $user_slot)
            $this->em->persist((new TownSlotReservation())->setTown($town)->setUser($user_slot));

        $this->em->persist($town);

        if (!empty( $header['townSchedule'] )) $town->setScheduledFor( $header['townSchedule'] );

        try {
            $this->em->flush();
            $this->profiler->recordTownCreated( $town, $creator, 'custom' );
            $this->em->flush();
        } catch (Exception $e) {
            return $this->error(exception: $e);
        }

        if ($header['townEventTag'] ?? false) {
            $this->em->persist($town->getRankingEntry()->setEvent(true));
            $this->em->flush();
        }

        if (!empty(array_filter($current_events, fn(EventConf $e) => $e->active()))) {
            if (!$this->townHandler->updateCurrentEvents($town, $current_events)) {
                $this->em->clear();
            } else try {
                $this->em->persist($town);
                $this->em->flush();
            } catch (Exception) {}
        }

        $incarnation = $creator
            ? $header['townIncarnation'] ?? ($creator->getRightsElevation() < User::USER_LEVEL_CROW ? 'incarnate' : 'none')
            : 'none';

        $incarnated = ($incarnation === 'incarnate') && !$force_disable_incarnate;

        if ($incarnated) {
            $citizen = $this->gameFactory->createCitizen($town, $creator, $error, $all);
            if (!$citizen) return $this->error();
            try {
                $this->em->persist($citizen);
                $this->em->flush();
                foreach ($all as $new_citizen)
                    $this->profiler->recordCitizenJoined( $new_citizen, $new_citizen === $citizen ? 'create' : 'follow' );
            } catch (Exception $e) {
                return $this->error(exception: $e);
            }

            try {
                $this->em->flush();
            } catch (Exception $e) {
                return $this->error(exception: $e);
            }
        }

        /** @var TownResult|CitizenResult $result */
        $result = (new class { use Optional, TownResult, CitizenResult; })->withTown($town);
        if ($incarnated) $result->withCitizen($citizen);

        return $result;
    }
}