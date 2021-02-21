<?php

namespace App\Controller;

use App\Controller\Admin\AdminActionController;
use App\Entity\Citizen;
use App\Entity\ExternalApp;
use App\Entity\Quote;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\EventConf;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(ConfMaster $conf, EntityManagerInterface $em, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih, TranslatorInterface $translator) {
        $this->conf = $conf;
        $this->entity_manager = $em;
        $this->time_keeper = $tk;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;
        $this->translator = $translator;
    }

    public function getUserLanguage(): string {
        if ($this->getUser() && $this->getUser()->getLanguage())
            return $this->getUser()->getLanguage();
        $l = Request::createFromGlobals()->getLocale();
        if ($l) $l = explode('_', $l)[0];
        return in_array($l, ['en','de','es','fr']) ? $l : 'de';
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
            'desc'      => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getName() : $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getDay() : "",
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getType()->getName() : "",
            'offset'    => timezone_offset_get( timezone_open( date_default_timezone_get ( ) ), new DateTime() )
        ];

        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
        if ($locale) $locale = explode('_', $locale)[0];
        if (!in_array($locale, ['de','en','es','fr'])) $locale = null;

        $quotes = $this->entity_manager->getRepository(Quote::class)->findBy(['lang' => $locale ?? 'de']);
        shuffle($quotes);

        $data['quote'] = $quotes[0];

        $data['apps'] = $this->entity_manager->getRepository(ExternalApp::class)->findBy(['active' => true]);

        $data['adminActions'] = AdminActionController::getAdminActions();

        if($this->getActiveCitizen() !== null && $this->getActiveCitizen()->getAlive()){
            $is_shaman = $this->citizen_handler->hasRole($this->getActiveCitizen(), 'shaman') || $this->getActiveCitizen()->getProfession()->getName() == 'shaman';
            $data['citizen'] = $this->getActiveCitizen();
            $data['conf'] = $this->getTownConf();
            $data['ap'] = $this->getActiveCitizen()->getAp();
            $data['max_ap'] = $this->citizen_handler->getMaxAP( $this->getActiveCitizen() );
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
            $data['is_night'] = $this->getActiveCitizen()->getTown()->isNight();
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        if ($this->getUser() && $this->getUser()->getActiveCitizen())
            $current_event = $this->conf->getCurrentEvent($this->getUser()->getActiveCitizen()->getTown());
        else $current_event = $this->conf->getCurrentEvent();

        if ($current_event->active()) {
            $parameters = array_merge($parameters, [
                'custom_css' => $current_event->get(EventConf::EVENT_CSS, 'event')
            ]);
        }

        return parent::render($view, $parameters, $response);
    }

    /**
     * @return Citizen|null The current citizen for the current user
     */
    protected function getActiveCitizen(): ?Citizen {
        /** @var User $user */
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