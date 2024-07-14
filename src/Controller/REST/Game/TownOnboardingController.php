<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\BeyondController;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\CalculateBlockTimeAction;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use App\Traits\Controller\ActiveCitizen;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/game/welcome', name: 'rest_game_welcome_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class TownOnboardingController extends AbstractController
{
    use ActiveCitizen;

    public function __construct(

    )
    {}

    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(TranslatorInterface $trans): JsonResponse {
        return new JsonResponse([
            'common' => [
                'help' => $trans->trans('Hilfe', [], 'global'),
                'confirm' => $trans->trans('Auswahl bestätigen', [], 'global'),
            ],
            'jobs' => [
                'headline' => $trans->trans('Wähle einen Beruf', [], 'game'),
                'in_town' => $trans->trans('Zur zeit in der Stadt', [], 'game'),
                'in_town_help' => $trans->trans('Die Bürger dieser Stadt haben die unten aufgeführten Berufe gewählt.', [], 'game'),
                'flavour' => $trans->trans('Du suchst dir besser eine Beschäftigung aus, denn es gilt: Ohne Job, kein Leben. Wozu bist du denn sonst gut? Die Gemeinschaft, der du angehören wirst, braucht jede Art von Personen. Die Zusammenarbeit mit den anderen wird zweifelsohne deine Überlebensdauer entscheidend beeinflussen...', [], 'game'),
            ]
        ]);
    }

    #[Route(path: '/{town}/professions', name: 'profession', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function professions(Town $town, EntityManagerInterface $em, ConfMaster $conf, Packages $asset, TranslatorInterface $trans): JsonResponse
    {
        $activeCitizen = $this->getActiveCitizen();
        if ($activeCitizen->getTown() !== $town || $activeCitizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $jobs = $em->getRepository(CitizenProfession::class)->findSelectable();
        $town = $activeCitizen->getTown();

        $disabledJobs = $conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, ['shaman']);

        return new JsonResponse(array_map(fn(CitizenProfession $profession) => [
            'id'     => $profession->getId(),
            'name'   => $trans->trans( $profession->getLabel(), [], 'game' ),
            'desc'   => $trans->trans( $profession->getDescription(), [], 'game' ),

            'icon'   => $asset->getUrl("build/images/professions/{$profession->getIcon()}.gif"),
            'poster' => $asset->getUrl("build/images/professions/select/{$profession->getIcon()}.gif"),
        ], array_filter( $jobs, fn( CitizenProfession $job ) => !in_array( $job->getName(), $disabledJobs, true ) )));
    }
}
