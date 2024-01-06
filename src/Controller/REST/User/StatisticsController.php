<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Service\Statistics\UserStatCollectionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/user-stats', name: 'rest_user_stats_lgc_', condition: "request.headers.get('Accept') === 'application/json'")]
#[Route(path: '/rest/v1/user/stats', name: 'rest_user_stats_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile('skip')]
class StatisticsController extends CustomAbstractCoreController
{
    /**
     * @param UserStatCollectionService $stats
     * @param string $dateDiff
     * @return JsonResponse
     */
    #[Route(path: '/daily-active-users', name: 'list-dau', methods: ['GET'], defaults: ['dateDiff' => '24hour'])]
    #[Route(path: '/monthly-active-users', name: 'list-mau', methods: ['GET'], defaults: ['dateDiff' => '30day'])]
    #[Route(path: '/yearly-active-users', name: 'list-yau', methods: ['GET'], defaults: ['dateDiff' => '1year'])]
    public function list(UserStatCollectionService $stats, string $dateDiff): JsonResponse {
        $cutoff = new \DateTime("now-$dateDiff");
        return new JsonResponse([
            'players' => $stats->collectData( $cutoff, $this->generatedLangsCodes ),
            'generated' => (new \DateTime('now'))->format('c'),
            'since' => $cutoff->format('c'),
        ]);
    }

}
