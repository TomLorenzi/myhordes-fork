<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Annotations\Toaster;
use App\Controller\BeyondController;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\HeroSkillPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Enum\Configuration\TownSetting;
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
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
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
use function RectorPrefix202403\React\Promise\map;
use function Symfony\Component\Clock\now;


#[Route(path: '/rest/v1/game/lobby', name: 'rest_game_lobby_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile(only_ghost: true)]
#[IsGranted('ROLE_USER')]
class GameOnboardingController extends AbstractController
{

    public function __construct(

    ) {}

    private function getTownClassAccessLimit(TownClass $class, MyHordesConf $conf): int {
        return match($class->getName()) {
            'remote' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 0 ),
            'panda'  => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 200 ),
            'custom' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 ),
            default => 0
        };
    }

    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(TranslatorInterface $trans, Packages $asset, EntityManagerInterface $em, ConfMaster $conf): JsonResponse {
        return new JsonResponse([
            'common' => [
                'help' => $trans->trans('Hilfe', [], 'global'),
                'warn' => $asset->getUrl( 'build/images/icons/warning_anim.gif' ),
            ],
            'flags' => [
                'de' => $asset->getUrl( 'build/images/lang/de.png' ),
                'en' => $asset->getUrl( 'build/images/lang/en.png' ),
                'fr' => $asset->getUrl( 'build/images/lang/fr.png' ),
                'es' => $asset->getUrl( 'build/images/lang/es.png' ),
                'multi' => $asset->getUrl( 'build/images/lang/multi.png' ),
            ],
            'types' => array_map(fn(TownClass $c) => [
                'id' => $c->getId(),
                'name' => $trans->trans($c->getLabel(), [], 'game'),
                'order' => $c->getOrderBy(),
                'help' => $trans->trans($c->getHelp(), ['splimit' => $this->getTownClassAccessLimit( $c, $conf->getGlobalConf() )], 'game'),
            ], $em->getRepository(TownClass::class)->findAll()),
            'table' => [
                'head' => [
                    'name' => $trans->trans('Stadtname', [], 'ghost'),
                    'citizens' => $trans->trans('Bürger', [], 'game'),
                    'coas' => $trans->trans('Kleine Koalitionen', [], 'game'),
                    'coas_help' => $trans->trans('Gibt an, wie viele Bürger diese Stadt gemeinsam mit einer Koalition betreten haben. Es werden sowohl Spieler gezählt die automatisch mit ihrer Koalition beitreten als auch solche, die manuell anderen Spielern aus ihrer Koalition folgen.', [], 'game'),
                ],
                'no_towns' => $trans->trans('Aktuell gibt es keine Stadt diesen Typs.', [], 'ghost'),
                'mayor' => $trans->trans('Diese Stadt wurde von einem Spieler gegründet.', [], 'ghost'),
                'mayor_icon' => $asset->getUrl( 'build/images/icons/small_user.gif' ),
                'mayor_lines' => [
                    $trans->trans('Sie folgt den normalen Spielregeln und wird an ihrem Ende ins Ranking aufgenommen.', [], 'ghost'),
                    $trans->trans('Wenn du dieser Stadt beitrittst, kannst du {block} Tage danach keiner anderen von einem Spieler gegründeten Stadt beitreten.', ['block' => 15], 'ghost'),
                ],
                'lang' => $trans->trans('Die Sprache dieser Stadt stimmt nicht mit deiner Spracheinstellung überein. Wenn du dieser Stadt beitrittst, musst du im Stadtforum die Sprache der Stadt verwenden.', [], 'ghost'),
                'lang_warn' => $trans->trans('Die Verwendung einer anderen Sprache im Stadtforum kann zu Sanktionen seitens der Moderation führen!', [], 'ghost'),
            ]
        ]);
    }

    private function generateToken( GenerateMercureToken $token ): array {
        return ($token)(
            topics: "myhordes://live/concerns/game-lobby",
            expiration: 300,
            renew_url: $this->generateUrl('rest_game_lobby_renew_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    #[Route(path: '/live', name: 'renew_token', methods: ['GET'])]
    public function renew_token(GenerateMercureToken $token): JsonResponse
    {
        return new JsonResponse(
            ['token' => $this->generateToken($token)]
        );
    }

    #[Route(path: '/list', name: 'list_towns', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $towns = $em->getRepository(Town::class)->matching((new Criteria())
            ->where(Criteria::expr()->eq('day', 1))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->isNull('scheduledFor'),
                Criteria::expr()->lte('scheduledFor', now()),
            ))
        )->filter(fn(Town $town) => $town->getPopulation() > $town->getCitizenCount());

        return new JsonResponse([
            'towns' => $towns->map(fn(Town $town) => [
                'id' => $town->getId(),
                'name' => $town->getName(),
                'population' => $town->getPopulation(),
                'language' => $town->getLanguage(),
                'citizenCount' => $town->getCitizenCount(),
                'type' => $town->getType()->getId(),
                'mayor' => $town->isMayor(),
                'coalitions' => $town->getCoalizedCitizenCount()
            ])->getValues()
        ]);
    }
}
