<?php

namespace App\Controller;

use App\Entity\Citizen;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CustomAbstractController
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

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null, $locale = null ): array {
        $data = $data ?? [];
        $data['menu_section'] = $section;

        $data['clock'] = [
            'desc'      => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getName() : $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getDay() : "",
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $this->getActiveCitizen() !== null ? $this->getActiveCitizen()->getTown()->getType()->getName() : "",
        ];

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
        }
        return $data;
    }
    
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

    protected function getActiveCitizen(): ?Citizen {
        /** @var User $user */
        $user = $this->getUser();
        if($user === null) return null;
        return $this->cache_active_citizen ?? ($this->cache_active_citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($user));
    }

    protected function getTownConf() {
        return $this->town_conf ?? ($this->town_conf = $this->conf->getTownConfiguration( $this->getActiveCitizen()->getTown() ));
    }
}