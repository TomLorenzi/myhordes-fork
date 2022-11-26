<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\TownClass;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;


/**
 * @Route("/rest/v1/user-stats", name="rest_user_stats_", condition="request.headers.get('Accept') === 'application/json'")
 * @GateKeeperProfile("skip")
 */
class UserStatisticsController extends CustomAbstractCoreController
{
    /**
     * @Route("/daily-active-users", name="list-dau", methods={"GET"}, defaults={"dateDiff"="24hour"})
     * @Route("/monthly-active-users", name="list-mau", methods={"GET"}, defaults={"dateDiff"="30day"})
     * @Route("/yearly-active-users", name="list-yau", methods={"GET"}, defaults={"dateDiff"="1year"})
     * @Cache(smaxage="43200", mustRevalidate=false, public=true)
     * @param EntityManagerInterface $em
     * @param string $dateDiff
     * @return JsonResponse
     */
    public function list(EntityManagerInterface $em, string $dateDiff): JsonResponse {

        $cutoff = new \DateTime("now-$dateDiff");

        $data = ['total' => 0, 'by_lang' => ['others' => 0], 'by_pronoun' => ['male' => 0, 'female' => 0, 'unset' => 0]];
        foreach ($this->generatedLangsCodes as $code) $data['by_lang'][$code] = 0;
        foreach ($e = $em->getRepository(User::class)->createQueryBuilder('u')
            ->select("count(u.id) as count, u.language, JSON_EXTRACT(u.settings, '$.preferred-pronoun') as pronoun")
            ->andWhere('u.validated = 1')
            ->andWhere('u.email NOT LIKE :d')->setParameter('d', '$%')
            ->andWhere('u.lastActionTimestamp > :cutoff')->setParameter( 'cutoff', new \DateTime("now-$dateDiff") )
            ->groupBy("u.language", 'pronoun')
            ->getQuery()->getResult() as $lang
        ) {
            if (isset($data['by_lang'][$lang['language']])) $data['by_lang'][$lang['language']] += $lang['count'];
            else $data['by_lang']['others'] += $lang['count'];

            switch ((int)$lang['pronoun']) {
                case User::PRONOUN_MALE: $data['by_pronoun']['male'] += $lang['count']; break;
                case User::PRONOUN_FEMALE: $data['by_pronoun']['female'] += $lang['count']; break;
                default: $data['by_pronoun']['unset'] += $lang['count']; break;
            };


            $data['total'] += $lang['count'];
        }

        if ($data['by_lang']['others'] <= 0) unset($data['by_lang']['others']);
        if ($data['by_pronoun']['unset'] <= 0) unset($data['by_pronoun']['unset']);

        return new JsonResponse([
            'players' => $data,
            'generated' => (new \DateTime('now'))->format('c'),
            'since' => $cutoff->format('c'),
        ]);
    }

}
