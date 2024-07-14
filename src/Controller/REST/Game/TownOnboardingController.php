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
use App\Service\Actions\Game\OnboardCitizenIntoTownAction;
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
            'identity' => [
                'headline' => $trans->trans('Deine Identität', [], 'game'),
                'help'  => $trans->trans('Diese Stadt erlaubt es, einen Spitznamen zu tragen.', [], 'game'),
                'field' => $trans->trans('Dein Name', [], 'ghost'),
                'validation1' => $trans->trans('Ein Spielername kann maximal {number} Zeichen lang sein. Es gelten die gleichen Beschränkungen wie bei Nutzernamen (keine Leer- und Sonderzeichen, keine Namen die wie die von Raben aussehen).', ['number' => 22], 'game'),
                'validation2' => $trans->trans('Freilassen, um normalen Spielernamen zu verwenden.', [], 'game'),
            ],
            'jobs' => [
                'headline' => $trans->trans('Wähle einen Beruf', [], 'game'),
                'help' => $trans->trans('Je nachdem, welchen Beruf du auswählst, stehen dir unterschiedliche Aktionen und Spielweisen in dieser Stadt zur Verfügung.', [], 'game'),
                'more' => $trans->trans('Klick hier, um mehr über diesen Beruf zu erfahren...', [], 'game'),
                'in_town' => $trans->trans('Zur zeit in der Stadt', [], 'game'),
                'in_town_help' => $trans->trans('Die Bürger dieser Stadt haben die unten aufgeführten Berufe gewählt.', [], 'game'),
                'flavour' => $trans->trans('Du suchst dir besser eine Beschäftigung aus, denn es gilt: Ohne Job, kein Leben. Wozu bist du denn sonst gut? Die Gemeinschaft, der du angehören wirst, braucht jede Art von Personen. Die Zusammenarbeit mit den anderen wird zweifelsohne deine Überlebensdauer entscheidend beeinflussen...', [], 'game'),
            ]
        ]);
    }

    private function fetchActiveCitizen(Town $town): ?Citizen {
        $activeCitizen = $this->getActiveCitizen();
        if ($activeCitizen?->getTown() !== $town || $activeCitizen?->getProfession()?->getName() !== CitizenProfession::DEFAULT)
            return null;
        return $activeCitizen;
    }

    #[Route(path: '/{town}', name: 'config', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function town_config(Town $town, ConfMaster $conf): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $townConf = $conf->getTownConfiguration($town);

        return new JsonResponse([
            'features' => [
                'job'       => true,
                'alias'     => $townConf->get( TownConf::CONF_FEATURE_CITIZEN_ALIAS, false ),
                'abilities' => false,
            ]
        ]);
    }

    #[Route(path: '/{town}', name: 'onboard', methods: ['PATCH'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function onboard_to_town(Town $town, EntityManagerInterface $em, ConfMaster $conf, JSONRequestParser $parser, OnboardCitizenIntoTownAction $action): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $townConf = $conf->getTownConfiguration($town);

        $disabledJobs = $conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, ['shaman']);
        $profession = $em->getRepository(CitizenProfession::class)->find( $parser->get_int( 'profession.id', -1 ) );
        if (!$profession || $profession->getName() === CitizenProfession::DEFAULT || in_array( $profession->getName(), $disabledJobs, true ))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $alias = $parser->trimmed('identity.name');
        if ($alias !== null) {
            if (
                !$townConf->get( TownConf::CONF_FEATURE_CITIZEN_ALIAS, false ) ||
                !is_string($alias) ||
                mb_strlen($alias) < 4 || mb_strlen($alias) > 22
            ) return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        $success = ($action)($activeCitizen, $profession, $alias);
        return $success ? new JsonResponse([
            'url' => $this->generateUrl('game_landing')
        ]) : new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    #[Route(path: '/{town}/professions', name: 'profession', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function professions(Town $town, EntityManagerInterface $em, ConfMaster $conf, Packages $asset, TranslatorInterface $trans): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $jobs = $em->getRepository(CitizenProfession::class)->findSelectable();
        $town = $activeCitizen->getTown();

        $disabledJobs = $conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, ['shaman']);

        return new JsonResponse(array_values(array_map(fn(CitizenProfession $profession) => [
            'id'     => $profession->getId(),
            'name'   => $trans->trans( $profession->getLabel(), [], 'game' ),
            'desc'   => $trans->trans( $profession->getDescription(), [], 'game' ),

            'icon'   => $asset->getUrl("build/images/professions/{$profession->getIcon()}.gif"),
            'poster' => $asset->getUrl("build/images/professions/select/{$profession->getIcon()}.gif"),
            'help'   => $this->generateUrl('help', ['name' => $profession->getName()])
        ], array_filter( $jobs, fn( CitizenProfession $job ) => !in_array( $job->getName(), $disabledJobs, true ) ))));
    }

    #[Route(path: '/{town}/citizens', name: 'citizens', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function citizens(Town $town, EntityManagerInterface $em): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        return new JsonResponse(
            $em->createQueryBuilder()
                ->select('COUNT(c.id) AS n', 'p.id AS id')
                ->from(Citizen::class, 'c')
                ->leftJoin(CitizenProfession::class, 'p', 'WITH', 'c.profession = p.id')
                ->where('c.town = :town')->setParameter('town', $town)
                ->groupBy('p.id')
                ->getQuery()->getArrayResult()
        );
    }
}
