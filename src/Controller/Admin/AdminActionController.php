<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Controller\CustomAbstractController;
use App\Entity\AttackSchedule;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\LogEntryTemplate;
use App\Entity\User;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\AdminHandler;
use App\Service\AdminLog;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Annotations\GateKeeperProfile;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
 */
class AdminActionController extends CustomAbstractController
{
    protected LogTemplateHandler $logTemplateHandler;
    protected ZoneHandler $zone_handler;
    protected UserHandler $user_handler;
    protected CrowService $crow_service;
    protected AdminLog $logger;
    protected UrlGeneratorInterface $urlGenerator;
    protected AdminHandler $adminHandler;

    public static function getAdminActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),   'route' => 'admin_dashboard'],
            ['name' => T::__('Users', 'admin'),       'route' => 'admin_users'],
            ['name' => T::__('Foren-Mod.', 'admin'),  'route' => 'admin_reports'],
            ['name' => T::__('Städte', 'admin'),      'route' => 'admin_town_list'],
            ['name' => T::__('Zukunft', 'admin'),     'route' => 'admin_changelogs'],
            ['name' => T::__('AntiSpam', 'admin'),    'route' => 'admin_spam_domain_view'],
            ['name' => T::__('Apps', 'admin'),        'route' => 'admin_app_view'],
            ['name' => T::__('Saisons', 'admin'),     'route' => 'admin_seasons_view'],
            ['name' => T::__('Gruppen', 'admin'),     'route' => 'admin_group_view'],
            ['name' => T::__('Dateisystem', 'admin'), 'route' => 'admin_file_system_dash'],
        ];
    }

    public static function getCommunityActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),  'route' => 'admin_dashboard'],
            ['name' => T::__('Zukunft', 'admin'),    'route' => 'admin_changelogs'],
        ];
    }

    public function __construct(EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $lth, TranslatorInterface $translator, ZoneHandler $zh, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih, UserHandler $uh, CrowService $crow, AdminLog $adminLogger, UrlGeneratorInterface $urlGenerator, AdminHandler $adminHandler)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->logTemplateHandler = $lth;
        $this->zone_handler = $zh;
        $this->user_handler = $uh;
        $this->crow_service = $crow;
        $this->logger = $adminLogger;
        $this->urlGenerator = $urlGenerator;
        $this->adminHandler = $adminHandler;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array
    {
        $data = parent::addDefaultTwigArgs($section, $data);

        $data["admin_tab"] = $section;

        return $data;
    }

    protected function renderLog( ?int $day, $town, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];

        # Try to fetch one more log to check if we must display the "show more entries" message
        $nb_to_fetch = (is_null($max) or $max <= 0) ? $max : $max + 1;

        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter(
            $town, $day, null, $zone, $type, $nb_to_fetch, null ) as $idx => $entity) {

                /** @var LogEntryTemplate $template */
                $template = $entity->getLogEntryTemplate();
                if (!$template)
                    continue;
                $entityVariables = $entity->getVariables();
                $entries[$idx]['timestamp'] = $entity->getTimestamp();
                $entries[$idx]['class'] = $template->getClass();
                $entries[$idx]['type'] = $template->getType();
                $entries[$idx]['id'] = $entity->getId();
                $entries[$idx]['hidden'] = $entity->getHidden();
                $entries[$idx]['hiddenBy'] = $entity->getHiddenBy();

                $variableTypes = $template->getVariableTypes();
                $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);

                try {
                    $entries[$idx]['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (Exception $e) {
                    $entries[$idx]['text'] = "null";
                }             
            }

        $show_more_entries = false;
        if ($nb_to_fetch != $max) {
            $show_more_entries = count($entries) > $max;
            $entries = array_slice($entries, 0, $max);
        }

        return $this->render( 'ajax/admin/towns/log_content.html.twig', [
            'entries' => $entries,
            'show_more_entries' => $show_more_entries,
            'canHideEntry' => false,
            'day' => $day
        ] );
    }

    /**
     * @Route("jx/admin/com/dash", name="admin_dashboard")
     * @param ParameterBagInterface $params
     * @return Response
     */
    public function dash(ParameterBagInterface $params): Response
    {
        return $this->render( 'ajax/admin/dash.html.twig', $this->addDefaultTwigArgs(null, [
            'actions' => $this->isGranted('ROLE_CROW') ? self::getAdminActions() : self::getCommunityActions(),
            'now' => time(),
            'schedules' => $this->isGranted('ROLE_ADMIN') ? $this->entity_manager->getRepository(AttackSchedule::class)->findByCompletion( false ) : [],
        ]));
    }


    /**
     * @Route("api/admin/login", name="api_admin_login")
     * @param TokenStorageInterface $ts
     * @return Response
     */
    public function login_api(TokenStorageInterface $ts): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user || !$user->getValidated() || $user->getRightsElevation() < User::USER_LEVEL_CROW) {
            $ts->setToken();
            return new AjaxResponse( ['success' => false ] );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/admin/com/action/{id}", name="admin_action", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function index(int $id): Response
    {
        $actions = $this->isGranted('ROLE_CROW') ? self::getAdminActions() : self::getCommunityActions();
        if (isset($actions[$id]) && isset($actions[$id]['route']))
            return $this->redirect($this->generateUrl($actions[$id]['route']));

        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }
}
