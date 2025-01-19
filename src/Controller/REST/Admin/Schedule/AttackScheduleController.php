<?php

namespace App\Controller\REST\Admin\Schedule;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AttackSchedule;
use App\Enum\Configuration\MyHordesSetting;
use App\Service\JSONRequestParser;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/admin/schedule/attack', name: 'rest_admin_schedule_attack_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_SUB_ADMIN')]
#[GateKeeperProfile('skip')]
class AttackScheduleController extends CustomAbstractCoreController
{
    private function current_delay( EntityManagerInterface $em ): JsonResponse {
        $planned = $em->getRepository(AttackSchedule::class)->findBy(['completed' => false, 'startedAt' => null], ['timestamp' => 'ASC']);

        if (count($planned) > 1)
            $em->remove( $planned[array_key_first($planned)] );
        else {;
            $planned = empty($planned) ? (new AttackSchedule())->setTimestamp( new DateTimeImmutable() ) : $planned[array_key_first($planned)];
            $datemod = $this->conf->getGlobalConf()->get(MyHordesSetting::NightlyAttackDateModifier);
            if ($datemod !== 'never') {
                $new_date = (new DateTime())->setTimestamp( $planned->getTimestamp()->getTimestamp() )->modify($datemod);
                if ($new_date !== false && $new_date > $planned->getTimestamp())
                    $em->persist( $planned->setTimestamp( DateTimeImmutable::createFromMutable($new_date)) );
                else return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);
            } else return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);
        }

        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    #[Route(path: '/current', name: 'find', methods: ['PATCH'])]
    public function modify(EntityManagerInterface $em, JSONRequestParser $parser): JsonResponse {
        if (!$parser->has('method', true))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        return match ( $parser->get('method') ) {
            'delay' => $this->current_delay( $em ),
            default => new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE)
        };
    }
}
