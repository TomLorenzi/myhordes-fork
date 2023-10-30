<?php

namespace App\Controller;

use App\Controller\Admin\AdminActionController;
use App\Entity\ExternalApp;
use App\Entity\GlobalPoll;
use App\Entity\Quote;
use App\Entity\UserSwapPivot;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\EventConf;
use App\Structures\TownConf;
use App\Traits\Controller\ActiveCitizen;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomAbstractController extends CustomAbstractCoreController {

    use ActiveCitizen;

    protected TownConf $town_conf;
    protected EntityManagerInterface $entity_manager;
    protected TimeKeeperService $time_keeper;
    protected CitizenHandler $citizen_handler;
    protected InventoryHandler $inventory_handler;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih, TranslatorInterface $translator) {
        parent::__construct($conf, $translator);

        $this->entity_manager = $em;
        $this->time_keeper = $tk;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;
    }

    private static int $flash_message_count = 0;
    protected function addFlash(string $type, $message): void {
        parent::addFlash( $type, [$message,++self::$flash_message_count] );
    }

    /**
     * Adds default arguments passed to the twig templates
     * @param string|null $section The section we are in (town sector, soul tab, etc...)
     * @param array|null $data Array of twig arguments
     * @return array The array of twig arguments with some default data
     */
    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];
        $data['menu_section'] = $section;

        $activeCitizen = $this->getActiveCitizen();
        $data['clock'] = [
            'desc'      => $activeCitizen?->getTown()->getName() ?? $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $activeCitizen?->getTown()->getDay() ?? '',
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $activeCitizen?->getTown()->getType()->getName() ?? '',
            'offset'    => timezone_offset_get( timezone_open( date_default_timezone_get ( ) ), new DateTime() )
        ];

        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
        if ($locale) $locale = explode('_', $locale)[0];
        if (!in_array($locale, $this->generatedLangsCodes)) $locale = null;

        $quotes = $this->entity_manager->getRepository(Quote::class)->findBy(['lang' => $locale ?? 'de']);
        shuffle($quotes);

        $allLangs = [];
        foreach ($this->generatedLangs as $lang)
            $allLangs[$lang['code']] = $lang;

        $data["langsCodes"] = $this->generatedLangsCodes;
        $data["allLangs"] = $allLangs;
        $data['quote'] = $quotes[0];

        $data['apps'] = $this->entity_manager->getRepository(ExternalApp::class)->findBy(['active' => true]);

        $data['adminActions'] = AdminActionController::getAdminActions();
        $data['comActions']   = AdminActionController::getCommunityActions();
        $data['swapPivots']   = $this->getUser() ? $this->entity_manager->getRepository(UserSwapPivot::class)->findBy( ['principal' => $this->getUser()] ) : [];

        $data["poll"] = array_values(array_filter(
                $this->entity_manager->getRepository(GlobalPoll::class)->findByState(false, true, false),
                fn(GlobalPoll $poll) => !$poll->getPoll()->getParticipants()->contains( $this->getUser() )
            ))[0] ?? null;

        if ( $activeCitizen?->getAlive() ){
            $is_shaman = $this->citizen_handler->hasRole($activeCitizen, 'shaman') || $activeCitizen->getProfession()->getName() == 'shaman';
            $data['citizen'] = $activeCitizen;
            $data['conf'] = $this->getTownConf();
            $data['ap'] = $activeCitizen->getAp();
            $data['max_ap'] = $this->citizen_handler->getMaxAP($activeCitizen);
            $data['has_wound'] = $this->citizen_handler->isWounded($activeCitizen);
            $data['banished'] = $activeCitizen->getBanished();
            $data['town_chaos'] = $activeCitizen->getTown()->getChaos();
            $data['bp'] = $activeCitizen->getBp();
            $data['max_bp'] = $this->citizen_handler->getMaxBP($activeCitizen);
            $data['status'] = $activeCitizen->getStatus();
            $data['roles'] = $activeCitizen->getVisibleRoles();
            $data['rucksack'] = $activeCitizen->getInventory();
            $data['rucksack_size'] = $this->inventory_handler->getSize( $activeCitizen->getInventory() );
            $data['pm'] = $activeCitizen->getPm();
            $data['max_pm'] = $this->citizen_handler->getMaxPM($activeCitizen);
            $data['username'] = $this->getUser()->getName();
            $data['is_shaman'] = $is_shaman;
            $data['is_shaman_job'] = $activeCitizen->getProfession()->getName() == 'shaman';
            $data['is_shaman_role'] = $this->citizen_handler->hasRole($activeCitizen, 'shaman');
            $data['hunger'] = $activeCitizen->getGhulHunger();
            $data['is_night'] = $this->getTownConf()->isNightTime();
        }
        return $data;
    }

    private function enrichParameter(array &$parameters): void {
        $town = $this->getUser()?->getActiveCitizen()?->getTown();
        $current_events = $town ? $this->conf->getCurrentEvents($town) : $this->conf->getCurrentEvents();

        $event_css = null;
        foreach ($current_events as $current_event) {
            if ($current_event->active() && ($css = $current_event->get(EventConf::EVENT_CSS, null))) {
                $event_css = $current_event->get(EventConf::EVENT_CSS, $css);
                break;
            }
        }

        $nightMode = $town && $this->conf->getTownConfiguration($this->getUser()->getActiveCitizen()->getTown())->isNightMode();

        $parameters = array_merge($parameters, [
            'theme' => [
                'themeContainer' => 1,
                'themeName' => $event_css ?? 'none',
                'themeDaytime' => $nightMode ? 'night' : 'day',
                'themePrimaryModifier' => $town?->getType()?->getName() ?? ' none',
                'themeSecondaryModifier' => match (true) {
                    $town?->getChaos() => 'chaos',
                    default => 'none'
                },
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $this->enrichParameter($parameters);
        return parent::render($view, $parameters, $response);
    }

    protected function renderBlocks(string $view, array $blocks, array $externals = [], array $parameters = [], $include_flash = true, Response $response = null, bool $wrap = false): Response
    {
        $this->enrichParameter($parameters);

        $twig = $this->container->get('twig');
        $template = $twig->load( $view );
        $args = $twig->mergeGlobals( $parameters );

        $blocks = array_map( fn($block) => $template->renderBlock($block, $args), $blocks );
        if ($include_flash) $blocks[] = $twig->render('ajax/flash.html.twig', $twig->mergeGlobals([]));

        foreach ($externals as $ext_view => $def) {
            list( $target, $ext_block ) = is_string($def) ? [$def,$def] : $def;

            if ($ext_block === '*') $ext_content = $twig->render($ext_view, $args);
            else $ext_content = $twig->load( $ext_view )->renderBlock($ext_block, $args );
            $blocks[] = "<div x-render-target='#{$target}'>$ext_content</div>";
        }

        $content = join('', $blocks);
        if ($wrap) $content = "<div>$content</div>";

        return parent::render( 'ajax/ajax_plain.html.twig', ['_ajax_base_content' => $content], $response );
    }

    /**
     * @return TownConf The current town settings
     */
    protected function getTownConf(): TownConf {
        return $this->town_conf ?? ($this->town_conf = $this->conf->getTownConfiguration( $this->getActiveCitizen()->getTown() ));
    }
}