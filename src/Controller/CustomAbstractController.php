<?php

namespace App\Controller;

use App\Controller\Admin\AdminActionController;
use App\Entity\Citizen;
use App\Entity\ExternalApp;
use App\Entity\GlobalPoll;
use App\Entity\Quote;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CustomAbstractController
 * @method User getUser
 */
class CustomAbstractController extends AbstractController {

    protected ConfMaster $conf;
    protected ?Citizen $cache_active_citizen = null;
    protected TownConf $town_conf;
    protected EntityManagerInterface $entity_manager;
    protected TimeKeeperService $time_keeper;
    protected CitizenHandler $citizen_handler;
    protected InventoryHandler $inventory_handler;
    protected TranslatorInterface $translator;
    protected array $generatedLangs;
    protected array $allLangs;
    protected array $generatedLangsCodes;
    protected array $allLangsCodes;

    public function __construct(ConfMaster $conf, EntityManagerInterface $em, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih, TranslatorInterface $translator) {
        $this->conf = $conf;
        $this->entity_manager = $em;
        $this->time_keeper = $tk;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;
        $this->translator = $translator;

        $this->allLangs = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_LANGS);
        $this->allLangsCodes = array_map(function($item) {return $item['code'];}, $this->allLangs);

        $this->generatedLangs = array_filter($this->allLangs, function($item) {
            return $item['generate'];
        });
        $this->generatedLangsCodes = array_map(function($item) {return $item['code'];}, $this->generatedLangs);

    }

    public function getUserLanguage( bool $ignore_profile_language = false ): string {
        if (!$ignore_profile_language && $this->getUser() && $this->getUser()->getLanguage())
            return $this->getUser()->getLanguage();

        $l = $this->container->get('request_stack')->getCurrentRequest()->getPreferredLanguage( array_diff( $this->allLangsCodes, ['ach'] ) );
        if ($l) $l = explode('_', $l)[0];
        return in_array($l, $this->allLangsCodes) ? $l : 'de';
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

        $data['clock'] = [
            'desc'      => $this->getActiveCitizen()?->getTown()->getName() ?? $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $this->getActiveCitizen()?->getTown()->getDay() ?? '',
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $this->getActiveCitizen()?->getTown()->getType()->getName() ?? '',
            'offset'    => timezone_offset_get( timezone_open( date_default_timezone_get ( ) ), new DateTime() )
        ];

        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
        if ($locale) $locale = explode('_', $locale)[0];
        if (!in_array($locale, $this->generatedLangsCodes)) $locale = null;

        $quotes = $this->entity_manager->getRepository(Quote::class)->findBy(['lang' => $locale ?? 'de']);
        shuffle($quotes);

        $data["langsCodes"] = $this->generatedLangsCodes;
        $data['quote'] = $quotes[0];

        $data['apps'] = $this->entity_manager->getRepository(ExternalApp::class)->findBy(['active' => true]);

        $data['adminActions'] = AdminActionController::getAdminActions();
        $data['comActions']   = AdminActionController::getCommunityActions();

        $data["poll"] = array_values(array_filter(
                $this->entity_manager->getRepository(GlobalPoll::class)->findByState(false, true, false),
                fn(GlobalPoll $poll) => !$poll->getPoll()->getParticipants()->contains( $this->getUser() )
            ))[0] ?? null;

        if ( $this->getActiveCitizen()?->getAlive() ){
            $is_shaman = $this->citizen_handler->hasRole($this->getActiveCitizen(), 'shaman') || $this->getActiveCitizen()->getProfession()->getName() == 'shaman';
            $data['citizen'] = $this->getActiveCitizen();
            $data['conf'] = $this->getTownConf();
            $data['ap'] = $this->getActiveCitizen()->getAp();
            $data['max_ap'] = $this->citizen_handler->getMaxAP( $this->getActiveCitizen() );
            $data['has_wound'] = $this->citizen_handler->isWounded($this->getActiveCitizen());
            $data['banished'] = $this->getActiveCitizen()->getBanished();
            $data['town_chaos'] = $this->getActiveCitizen()->getTown()->getChaos();
            $data['bp'] = $this->getActiveCitizen()->getBp();
            $data['max_bp'] = $this->citizen_handler->getMaxBP( $this->getActiveCitizen() );
            $data['status'] = $this->getActiveCitizen()->getStatus();
            $data['roles'] = $this->getActiveCitizen()->getVisibleRoles();
            $data['rucksack'] = $this->getActiveCitizen()->getInventory();
            $data['rucksack_size'] = $this->inventory_handler->getSize( $this->getActiveCitizen()->getInventory() );
            $data['pm'] = $this->getActiveCitizen()->getPm();
            $data['max_pm'] = $this->citizen_handler->getMaxPM( $this->getActiveCitizen() );
            $data['username'] = $this->getUser()->getName();
            $data['is_shaman'] = $is_shaman;
            $data['is_shaman_job'] = $this->getActiveCitizen()->getProfession()->getName() == 'shaman';
            $data['is_shaman_role'] = $this->citizen_handler->hasRole($this->getActiveCitizen(), 'shaman');
            $data['hunger'] = $this->getActiveCitizen()->getGhulHunger();
            $data['is_night'] = $this->getTownConf()->isNightTime();
        }
        return $data;
    }

    private function enrichParameter(array &$parameters): void {
        if ($this->getUser() && $this->getUser()->getActiveCitizen())
            $current_events = $this->conf->getCurrentEvents($this->getUser()->getActiveCitizen()->getTown());
        else $current_events = $this->conf->getCurrentEvents();

        foreach ($current_events as $current_event) {
            if ($current_event->active() && ($css = $current_event->get(EventConf::EVENT_CSS, null))) {
                $parameters = array_merge($parameters, [
                    'custom_css' => $current_event->get(EventConf::EVENT_CSS, $css)
                ]);
                break;
            }
        }
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
     * @return Citizen|null The current citizen for the current user
     */
    protected function getActiveCitizen(): ?Citizen {
        $user = $this->getUser();
        if($user === null) return null;
        return $this->cache_active_citizen ?? ($this->cache_active_citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($user));
    }

    /**
     * @return TownConf The current town settings
     */
    protected function getTownConf(): TownConf {
        return $this->town_conf ?? ($this->town_conf = $this->conf->getTownConfiguration( $this->getActiveCitizen()->getTown() ));
    }
}