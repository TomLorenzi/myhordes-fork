<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\BeyondController;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\HeroSkillPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\CalculateBlockTimeAction;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\Actions\Game\CountCitizenProfessionsAction;
use App\Service\Actions\Game\OnboardCitizenIntoTownAction;
use App\Service\Actions\Security\GenerateMercureToken;
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
use MyHordes\Plugins\Fixtures\HeroSkill;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function index(TranslatorInterface $trans, Packages $asset): JsonResponse {
        return new JsonResponse([
            'common' => [
                'help' => $trans->trans('Hilfe', [], 'global'),
                'confirm' => $trans->trans('Auswahl bestätigen', [], 'global'),
                'continue' => $trans->trans('Nächste Seite', [], 'soul'),
                'return' => $trans->trans('Vorherige Seite', [], 'soul'),

                'on' => $asset->getUrl('build/images/icons/player_online.gif'),
                'off' => $asset->getUrl('build/images/icons/player_offline.gif'),
                'lock' => $asset->getUrl('build/images/icons/lock.gif'),
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
            ],
            'skills' => [
                'headline' => $trans->trans('Wähle deine Fähigkeiten', [], 'game'),
                'help' => $trans->trans('Deine Fähigkeiten verleihen dir bestimmte Boni im Spiel. Du kannst nicht alle auf einmal aktivieren, also wähle weise!', [], 'game'),
                'level' => $trans->trans('Level {skill-level}', [], 'game'),
                'pts' => $trans->trans('Dir stehen noch <em>{pts}</em> Punkte zur Verfügung!', [], 'game'),
            ]
        ]);
    }

    private function fetchActiveCitizen(Town $town): ?Citizen {
        $activeCitizen = $this->getActiveCitizen();
        if ($activeCitizen?->getTown() !== $town || $activeCitizen?->getProfession()?->getName() !== CitizenProfession::DEFAULT)
            return null;
        return $activeCitizen;
    }

    private function generateToken( Town $town, GenerateMercureToken $token ): array {
        return ($token)(
            topics: "myhordes://live/concerns/town-lobby/{$town->getId()}",
            expiration: 300,
            renew_url: $this->generateUrl('rest_game_welcome_renew_token', ['town' => $town->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
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
                'skills'    => true,
            ]
        ]);
    }

    #[Route(path: '/{town}/live', name: 'renew_token', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function renew_token(Town $town, GenerateMercureToken $token): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        return new JsonResponse(
            ['token' => $this->generateToken($town, $token)]
        );
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

    #[Route(path: '/{town}/skills', name: 'skills', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function skills(Town $town, EntityManagerInterface $em, Packages $asset, TranslatorInterface $trans): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $all_skills = $em->getRepository(HeroSkillPrototype::class)->findBy(['enabled' => true], [
            'legacy' => Order::Descending->value,
            'sort' => Order::Ascending->value,
            'level' => Order::Ascending->value,
            'id' => Order::Ascending->value,
        ]);

        $legacy_skills = array_filter($all_skills, fn(HeroSkillPrototype $s) => $s->isLegacy());
        $skills = array_filter($all_skills, fn(HeroSkillPrototype $s) => !$s->isLegacy());
        $skill_groups = [];
        foreach ($skills as $skill)
            if (!in_array($skill->getGroupIdentifier(), $skill_groups, true))
                $skill_groups[] = $skill->getGroupIdentifier();


        return new JsonResponse([
            'legacy' => [
                'level' => $activeCitizen->getUser()->getAllHeroDaysSpent(),
                'list' => array_values(array_map(fn(HeroSkillPrototype $p) => [
                    'id' => $p->getId(),
                    'title' => $trans->trans($p->getTitle(), [], 'game'),
                    'description' => $trans->trans($p->getDescription(), [], 'game'),
                    'icon' => $asset->getUrl("build/images/heroskill/{$p->getIcon()}.gif"),
                    'needed' => $p->getDaysNeeded()
                ], $legacy_skills))
            ],
            'skills' => [
                'pts' => 5,
                'groups' => array_map(fn(string $s) => $trans->trans($s, [], 'game'), $skill_groups),
                'list' => array_values(array_map(fn(HeroSkillPrototype $p) => [
                    'id' => $p->getId(),
                    'title' => $trans->trans($p->getTitle(), [], 'game'),
                    'description' => $trans->trans($p->getDescription(), [], 'game'),
                    'icon' => $asset->getUrl("build/images/heroskill/{$p->getIcon()}.gif"),
                    'level' => $p->getLevel(),
                    'sort' => $p->getSort(),
                    'group' => $trans->trans($p->getGroupIdentifier(), [], 'game')
                ], $skills))
            ]
        ]);
    }

    #[Route(path: '/{town}/citizens', name: 'citizens', methods: ['GET'])]
    #[GateKeeperProfile(only_incarnated: true)]
    public function citizens(Town $town, GenerateMercureToken $token, CountCitizenProfessionsAction $counter): JsonResponse
    {
        $activeCitizen = $this->fetchActiveCitizen($town);
        if (!$activeCitizen) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        return new JsonResponse([
            'list' => ($counter)($town),
            'token' => $this->generateToken($town, $token),
        ]);
    }
}
