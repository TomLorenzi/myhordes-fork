<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\GhostController;
use App\Entity\AccountRestriction;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\MayorMark;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Response\AjaxResponse;
use App\Service\Actions\Ghost\ExplainTownConfigAction;
use App\Service\Actions\Security\GenerateMercureToken;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\Clock\now;

/**
 * @method User getUser
 */
#[Route(path: '/rest/v1/game/lobby', name: 'rest_game_lobby_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile(only_ghost: true)]
#[IsGranted('ROLE_USER')]
class GameOnboardingController extends AbstractController
{
    public function __construct(
        private readonly UserHandler $userHandler
    ) {

    }

    private function checkTownTypeAccess(User $user, TownClass $class, MyHordesConf $conf): ?bool {
        $sp = $this->userHandler->fetchSoulPoints($user, useCached: true);

        return match($class->getName()) {
            TownClass::EASY   =>
                ($sp < $conf->get( MyHordesSetting::SoulPointRequirementRemote )
                || $sp >= $conf->get( MyHordesSetting::SoulPointRequirementSmallReturn )),
            default           => $sp >= $this->getTownClassAccessLimit($class, $conf)
        };
    }

    private function getTownClassAccessLimit(TownClass $class, MyHordesConf $conf): int {
        return match($class->getName()) {
            TownClass::DEFAULT  => $conf->get( MyHordesSetting::SoulPointRequirementRemote ),
            TownClass::HARD     => $conf->get( MyHordesSetting::SoulPointRequirementPanda ),
            TownClass::CUSTOM   => $conf->get( MyHordesSetting::SoulPointRequirementCustom ),
            default => 0
        };
    }

    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(TranslatorInterface $trans, Packages $asset, EntityManagerInterface $em, ConfMaster $conf): JsonResponse {
        $has_ticket = $this->userHandler->checkFeatureUnlock( $this->getUser(), 'f_sptkt', false );

        return new JsonResponse([
            'common' => [
                'help' => $trans->trans('Hilfe', [], 'global'),
                'warn' => $asset->getUrl( 'build/images/icons/warning_anim.gif' ),
                'plus' => $asset->getUrl( 'build/images/icons/small_more.gif' ),
                'death' => $asset->getUrl( 'build/images/professions/death.gif' ),
                'close' => $trans->trans('Schließen', [], 'global'),
                'lock' => $asset->getUrl( 'build/images/item/item_lock.gif' ),
                'rules' => $asset->getUrl( 'build/images/icons/small_rp.gif' ),
                'cancel' => $trans->trans('Abbrechen', [], 'global'),
            ],
            'flags' => [
                'de' => $asset->getUrl( 'build/images/lang/de.png' ),
                'en' => $asset->getUrl( 'build/images/lang/en.png' ),
                'fr' => $asset->getUrl( 'build/images/lang/fr.png' ),
                'es' => $asset->getUrl( 'build/images/lang/es.png' ),
                'multi' => $asset->getUrl( 'build/images/lang/multi.png' ),
            ],
            'professions' => array_map( fn(CitizenProfession $p) => [
                'name' => $p->getName(),
                'icon' => $asset->getUrl( "build/images/professions/{$p->getIcon()}.gif"),
            ], $em->getRepository(CitizenProfession::class)->findAll()),
            'types' => array_map(fn(TownClass $c) => [
                'id' => $c->getId(),
                'name' => $trans->trans($c->getLabel(), [], 'game'),
                'order' => $c->getOrderBy(),
                'help' => $trans->trans($c->getHelp(), ['splimit' => $this->getTownClassAccessLimit( $c, $conf->getGlobalConf() )], 'game'),
                'access' => $has_ticket || $this->checkTownTypeAccess( $this->getUser(), $c, $conf->getGlobalConf() )
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
                'show_players' => $trans->trans('Die Liste der eingeschriebenen Spieler ansehen.', [], 'game'),
                'password' => $trans->trans('Diese Stadt ist durch ein Passwort geschützt.', [], 'ghost'),
                'whitelist' => $trans->trans('Für diese Stadt gilt eine Platzreservierung.', [], 'ghost'),
                'rules' => $trans->trans('Die Regeln dieser Stadt weichen von der Norm ab:', [], 'ghost'),
                'more_info' => $trans->trans('Klicke auf den Namen der Stadt, um weitere Informationen zu erhalten.', [], 'ghost'),
                'event' => $trans->trans('Event-Stadt', [], 'ghost'),
                'event_help' => $trans->trans('Dies ist eine Event-Stadt, sie wird nach ihrem Ende nicht im Ranking erscheinen. Möglicherweise gelten unübliche Regeln für diese Stadt.', [], 'ghost'),
            ],
            'details' => [
                'join' => $trans->trans('Dieser Stadt beitreten', [], 'ghost'),
                'headline' => $trans->trans('Möchtest du die schreckliche Welt der Verdammten als Bürge der Stadt {town_name} betreten', [], 'ghost'),
                'in_town' => $trans->trans('Die folgenden Bürger sind der Stadt bereits beigetreten:', [], 'game'),
                'event1' => $trans->trans('Dies ist eine Event-Stadt, in der möglicherweise andere Regeln gelten und/oder eine bestimmte Spielweise erforderlich ist.', [], 'ghost'),
                'event2' => $trans->trans('Damit alle Spieler an diesem Event Spaß haben können, tritt dieser Stadt bitte nur bei, wenn du dir über deren Regeln im Klaren bist.', [], 'ghost'),
                'password' => $trans->trans('Bitte gib das Stadtpasswort ein :', [], 'ghost'),
                'coa' => $trans->trans('Wenn du eine Stadt betrittst, werden folgende Mitglieder deiner {coalition} dir folgen:', [
                    'coalition' => $trans->trans('Koalition', [], 'ghost'),
                ], 'ghost'),
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

    private function renderTown(Town $town, EntityManagerInterface $em): array {
        return [
            'id' => $town->getId(),
            'name' => $town->getName(),
            'population' => $town->getPopulation(),
            'language' => $town->getLanguage(),
            'citizenCount' => $town->getCitizenCount(),
            'type' => $town->getType()->getId(),
            'mayor' => $town->isMayor(),
            'event' => $town->getRankingEntry()?->getEvent() ?? false,
            'coalitions' => $town->getCoalizedCitizenCount(),
            'protection' => [
                'password' => !!$town->getPassword(),
                'whitelist' => $em->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0,
            ],
            'custom_rules' => !!$town->getConf(),
        ];
    }

    private function renderCitizen(User|Citizen $citizen): array {
        $is_user = is_a($citizen, User::class);
        return [
            'id' => $is_user ? $citizen->getId() : $citizen->getUser()->getId(),
            'name' => $citizen->getName(),
            'alive' => $is_user ? false : $citizen->getAlive(),
            'profession' => (!$is_user && $citizen->getAlive()) ? $citizen->getProfession()->getName() : null,
        ];
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
            'towns' => $towns->map(fn(Town $town) => $this->renderTown($town, $em))->getValues()
        ]);
    }

    #[Route(path: '/{id}/citizens', name: 'list_town_citizens', methods: ['GET'])]
    public function citizens(Town $town): JsonResponse
    {
        if (!$town->isOpen() || ($town->getScheduledFor() && $town->getScheduledFor() > now()))
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        return new JsonResponse([
            'citizens' => $town->getCitizens()->map(fn(Citizen $citizen) => $this->renderCitizen($citizen))->getValues()
        ]);
    }

    private function translateErrorCode(int $error, Town $town, MyHordesConf $conf, TranslatorInterface $translator): string {
        return match ($error) {
            GameFactory::ErrorTownClosed => $translator->trans('Die gewählte Stadt ist bereits voll.', [], 'ghost'),
            GameFactory::ErrorSoulPointsRequired => $translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $this->getTownClassAccessLimit( $town->getType(), $conf )], 'ghost' ),
            GameFactory::ErrorNotOnWhitelist => $translator->trans( 'Du kannst dieser Stadt nicht beitreten, da du nicht auf der Teilnehmerliste stehst.', [], 'game' ),
            GameFactory::ErrorUserAlreadyInGame => $translator->trans( 'Du kannst nicht mehr als einer Stadt gleichzeitig beitreten.', [], 'ghost' ),
            GameFactory::ErrorUserAlreadyInTown => $translator->trans( 'Du bist dieser Stadt bereits beigetreten.', [], 'ghost' ),
            ErrorHelper::ErrorPermissionError => $translator->trans( 'Du bist dieser Stadt aufgrund einer aktuellen Beschränkung deines Accounts nicht beitreten.', [], 'ghost' ),
            default => $translator->trans( 'Du kannst dieser Stadt nicht beitreten. Fehlercode: {error}', ['error' => $error], 'ghost' ),
        };
    }

    #[Route(path: '/{id}', name: 'town_details', methods: ['GET'])]
    public function details(Town $town, ExplainTownConfigAction $explainTownConfigAction,
                            EntityManagerInterface $em, ConfMaster $conf, TranslatorInterface $translator,
                            GameFactory $gameFactory,
    ): JsonResponse
    {
        if (!$town->isOpen() || ($town->getScheduledFor() && $town->getScheduledFor() > now()))
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        $warnings = [];
        $lock_reasons = [];

        $whitelist = $em->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0;
        if (!$gameFactory->userCanEnterTown(  $town, $this->getUser(), $whitelist, $error ))
            $lock_reasons[] = $this->translateErrorCode($error, $town, $conf->getGlobalConf(), $translator);

        $coa_members = [];
        if (empty($lock_reasons)) {
            $this->userHandler->getConsecutiveDeathLock( $this->getUser(), $cdm_warn );
            if ($cdm_warn) $warnings[] = $translator->trans('Du bist in mehreren deiner letzten Städte frühzeitig durch Verdursten gestorben. Sollte dies in deiner nächsten Stadt erneut geschehen, wirst du für einige Wochen vom Spiel ausgeschlossen.', [], 'ghost' );

            $coa_members = $this->userHandler->getAvailableCoalitionMembers( $this->getUser() , $count, $active);
            $warnings[] = match (true) {
                $count > 1 && ($whitelist || $town->getPassword()) => $translator->trans('Diese Stadt verfügt über eine Zugangsbeschränkung. Die Mitglieder deiner Koalition können dir nicht automatisch folgen, wenn du diese Stadt betrittst. Fortfahren?', [], 'ghost'),
                $count > 1 && !$active => $translator->trans('Achtung: Du stehst deiner {coalition} NICHT ZUR VERFÜGUNG! Das bedeutet, dass du diese Partie allein spielen wirst.', [
                    'coalition' => $translator->trans('Koalition', [], 'ghost'),
                ], 'ghost' ),
                $count > 1 && empty($coa_members) => $translator->trans('Achtung: Im Moment steht kein Mitglied deiner {coalition} zur Verfügung, um dir in eine Stadt zu folgen.', [
                    'coalition' => $translator->trans('Koalition', [], 'ghost'),
                ], 'ghost' ),
                $count > 1 && (count($coa_members) + 1) < $count => $translator->trans('Achtung: Im Moment stehen nicht alle Mitglieder deiner {coalition} zur Verfügung, um dir in eine Stadt zu folgen.', [
                    'coalition' => $translator->trans('Koalition', [], 'ghost'),
                ], 'ghost' ),
                default => null,
            };

        }

        return new JsonResponse([
            'town' => $this->renderTown($town, $em),
            'rules' => ($explainTownConfigAction)($town),
            'locks' => $lock_reasons,
            'warnings' => array_values( array_filter($warnings, fn($v) => $v !== null) ),
            'coa' => ($whitelist || $town->getPassword()) ? [] : array_map(fn(User $u) => $this->renderCitizen($u), $coa_members),
            'citizens' => $town->getCitizens()->map(fn(Citizen $citizen) => $this->renderCitizen($citizen))->getValues()
        ]);
    }

    #[Route(path: '/{id}', name: 'town_join', methods: ['POST'])]
    #[Semaphore(scope: 'global')]
    public function join(Town $town, JSONRequestParser $parser, GameFactory $factory, TranslatorInterface $translator,
                             EntityManagerInterface $em, ConfMaster $conf, TownHandler $townHandler): JsonResponse
    {
        $user = $this->getUser();

        if ($this->userHandler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
            return new JsonResponse(
                ['error' => 'message', 'message' => $this->translateErrorCode( ErrorHelper::ErrorPermissionError, $town, $conf->getGlobalConf(), $translator )],
                Response::HTTP_NOT_ACCEPTABLE
            );

        if ($this->userHandler->isRestricted( $user, AccountRestriction::RestrictionGameplayLang ))
            return new JsonResponse(
                ['error' => 'message', 'message' => $this->translateErrorCode( ErrorHelper::ErrorPermissionError, $town, $conf->getGlobalConf(), $translator )],
                Response::HTTP_NOT_ACCEPTABLE
            );

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return new JsonResponse(
                ['error' => ErrorHelper::ErrorActionNotAvailable],
                Response::HTTP_NOT_ACCEPTABLE
            );

        if(!empty($town->getPassword()) && $town->getPassword() !== $parser->get('pass', ''))
            return new JsonResponse(
                ['error' => GhostController::ErrorWrongTownPassword],
                Response::HTTP_NOT_ACCEPTABLE
            );

        $whitelist = $em->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0;
        if (!$factory->userCanEnterTown( $town, $user, $whitelist, $error ))
            return new JsonResponse(
                ['error' => 'message', 'message' => $this->translateErrorCode( $error, $town, $conf->getGlobalConf(), $translator )],
                Response::HTTP_NOT_ACCEPTABLE
            );

        $citizen = $factory->createCitizen($town, $user, $error, $all);
        if (!$citizen) return new JsonResponse(
            ['error' => $error],
            Response::HTTP_NOT_ACCEPTABLE
        );

        try {
            $em->persist($town);
            $em->persist($citizen);
            if ($town->isMayor() && $town->getCreator()?->getId() !== $user->getId())
                $em->persist( (new MayorMark())
                    ->setUser( $this->getUser() )
                    ->setCitizen( true )
                    ->setExpires( (new DateTime())->modify('+15days') )
                );
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => ErrorHelper::ErrorDatabaseException],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $current_town_events = $conf->getCurrentEvents($town);
        if (!empty(array_filter($current_town_events,fn(EventConf $e) => $e->active()))) {
            if (!$townHandler->updateCurrentEvents($town, $current_town_events))
                $em->clear();
            else {
                $em->persist($town);
                $em->flush();
            }
        }

        if (!$town->isOpen()){
            // Target town is closed, let's add special voting actions !
            $roles = $em->getRepository(CitizenRole::class)->findVotable();
            /** @var CitizenRole $role */
            foreach ($roles as $role){
                /** @var SpecialActionPrototype $special_action */
                $special_action = $em->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => 'special_vote_' . $role->getName()]);
                /** @var Citizen $citizen */
                foreach ($town->getCitizens() as $citizen){
                    if(!$citizen->getProfession()->getHeroic()) continue;

                    if(!$citizen->getSpecialActions()->contains($special_action)) {
                        $citizen->addSpecialAction($special_action);
                        $em->persist($citizen);
                    }
                }
            }
        }

        return new JsonResponse(['url' => $this->generateUrl('game_landing')]);
    }
}
