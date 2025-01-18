<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
use App\Entity\AttackSchedule;
use App\Enum\Configuration\MyHordesSetting;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminScheduleController extends AdminActionController
{

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/schedule/attack', name: 'admin_schedule_attacks')]
    public function attack_index(): Response
    {
        $planned = $this->entity_manager->getRepository(AttackSchedule::class)->findBy(['completed' => false], ['timestamp' => 'ASC']);
        $last_scheduled_attack = ($planned[array_key_last( $planned ) ?? 0] ?? null)?->getTimestamp();

        $projection = null;
        if ($last_scheduled_attack) {
            $datemod = $this->conf->getGlobalConf()->get(MyHordesSetting::NightlyAttackDateModifier);
            if ($datemod !== 'never') {
                $new_date = (new DateTime())->setTimestamp( $last_scheduled_attack->getTimestamp() )->modify($datemod);
                if ($new_date !== false && $new_date > $last_scheduled_attack)
                    $projection = DateTimeImmutable::createFromMutable($new_date);
            }
        }

        return $this->render( 'ajax/admin/scheduler/attacks.html.twig', $this->addDefaultTwigArgs(null, [
            'now' => new \DateTimeImmutable(),
            'projected' => $projection,
            'completed_schedules' => $this->entity_manager->getRepository(AttackSchedule::class)->findBy(['completed' => true], ['timestamp' => 'DESC'], 25),
            'planned_schedules'   => $planned,
        ]));
    }
}