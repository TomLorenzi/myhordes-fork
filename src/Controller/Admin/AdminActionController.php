<?php

namespace App\Controller\Admin;

use App\Controller\CustomAbstractController;
use App\Entity\AttackSchedule;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\AdminHandler;
use App\Service\AdminLog;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Annotations\GateKeeperProfile;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminActionController extends CustomAbstractController
{
    protected LogTemplateHandler $logTemplateHandler;
    protected ZoneHandler $zone_handler;
    protected UserHandler $user_handler;
    protected CrowService $crow_service;
    protected AdminLog $logger;
    protected UrlGeneratorInterface $urlGenerator;
    protected AdminHandler $adminHandler;
	protected TownHandler $town_handler;

    protected InvalidateTagsInAllPoolsAction $clear;

    public static function getAdminActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),   'route' => 'admin_dashboard'],
            ['name' => T::__('Kampagnen', 'admin'),   'route' => 'admin_campaigns'],
            ['name' => T::__('Users', 'admin'),       'route' => 'admin_users'],
            ['name' => T::__('Foren-Mod.', 'admin'),  'route' => 'admin_reports_forum_posts'],
            ['name' => T::__('Städte', 'admin'),      'route' => 'admin_town_list'],
            ['name' => T::__('Zukunft', 'admin'),     'route' => 'admin_changelogs'],
            ['name' => T::__('AntiSpam', 'admin'),    'route' => 'admin_spam_domain_view'],
            ['name' => T::__('Apps', 'admin'),        'route' => 'admin_app_view'],
            ['name' => T::__('Saisons', 'admin'),     'route' => 'admin_seasons_view'],
            ['name' => T::__('Gruppen', 'admin'),     'route' => 'admin_group_view'],
            ['name' => T::__('Dateisystem', 'admin'), 'route' => 'admin_file_system_dash'],
            ['name' => T::__('Angriffsplan', 'admin'),'route' => 'admin_schedule_attacks'],
        ];
    }

    public static function getCommunityActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),  'route' => 'admin_dashboard'],
            ['name' => T::__('Kampagnen', 'admin'),  'route' => 'admin_campaigns'],
            ['name' => T::__('Zukunft', 'admin'),    'route' => 'admin_changelogs'],
            ['name' => T::__('Kurztexte', 'admin'),  'route' => 'admin_reports_snippets'],
        ];
    }

    public function __construct(EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $lth, TranslatorInterface $translator, ZoneHandler $zh, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih, UserHandler $uh, CrowService $crow, AdminLog $adminLogger, UrlGeneratorInterface $urlGenerator, AdminHandler $adminHandler, TownHandler $townHandler, HookExecutor $hookExecutor, InvalidateTagsInAllPoolsAction $clear)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->logTemplateHandler = $lth;
        $this->zone_handler = $zh;
        $this->user_handler = $uh;
        $this->crow_service = $crow;
        $this->logger = $adminLogger;
        $this->urlGenerator = $urlGenerator;
        $this->adminHandler = $adminHandler;
		$this->town_handler = $townHandler;
        $this->clear = $clear;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array
    {
        $data = parent::addDefaultTwigArgs($section, $data);

        $data["admin_tab"] = $section;

        return $data;
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/com/dash', name: 'admin_dashboard')]
    public function dash(): Response
    {
        return $this->render( 'ajax/admin/dash.html.twig', $this->addDefaultTwigArgs(null, [
            'actions' => $this->isGranted('ROLE_CROW') ? self::getAdminActions() : self::getCommunityActions(),
        ]));
    }


    /**
     * @param TokenStorageInterface $ts
     * @return Response
     */
    #[Route(path: 'api/admin/login', name: 'api_admin_login')]
    public function login_api(TokenStorageInterface $ts): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();
        if ($user === null || !$user->getValidated() || $user->getRightsElevation() < User::USER_LEVEL_CROW) {
            $ts->setToken(null);
            return new AjaxResponse( ['success' => false ] );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/com/action/{id}', name: 'admin_action', requirements: ['id' => '\d+'])]
    public function index(int $id): Response
    {
        $actions = $this->isGranted('ROLE_CROW') ? self::getAdminActions() : self::getCommunityActions();
        if (isset($actions[$id]) && isset($actions[$id]['route']))
            return $this->redirect($this->generateUrl($actions[$id]['route']));

        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }

	public function getOrderedItemPrototypes($lang): array {
        $itemPrototypes = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        usort($itemPrototypes, function ($a, $b) use($lang) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'items', $lang), $this->translator->trans($b->getLabel(), [], 'items', $lang));
        });
		return $itemPrototypes;
	}

	public function getOrderedCitizenStatus($lang): array {
		if (apcu_enabled()) {
			$citizenStati = apcu_fetch("citizen_status_" . $lang);
			if (false === $citizenStati) {
				$citizenStati = $this->entity_manager->getRepository(CitizenStatus::class)->findAll();
				usort($citizenStati, function ($a, $b) use ($lang) {
					return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
				});
				apcu_store("citizen_status_" . $lang, $citizenStati);
			}
		} else {
			$citizenStati = $this->entity_manager->getRepository(CitizenStatus::class)->findAll();
			usort($citizenStati, function ($a, $b) use ($lang) {
				return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
			});
		}
		return $citizenStati;
	}

	public function getOrderedCitizenRoles($lang): array {
		if (apcu_enabled()) {
			$citizenRoles = apcu_fetch("citizen_roles_" . $lang);
			if (false === $citizenRoles) {
				$citizenRoles = $this->entity_manager->getRepository(CitizenRole::class)->findAll();
				usort($citizenRoles, function ($a, $b) use ($lang) {
					return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
				});
				apcu_store("citizen_roles_" . $lang, $citizenRoles);
			}
		} else {
			$citizenRoles = $this->entity_manager->getRepository(CitizenRole::class)->findAll();

			usort($citizenRoles, function ($a, $b) use ($lang) {
				return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
			});
		}
		return $citizenRoles;
	}

	public function getOrderedPictoPrototypes($lang): array {
		if (apcu_enabled()) {
			$pictoProtos = apcu_fetch("pictos_prototypes_" . $lang);
			if (false === $pictoProtos) {
				$pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
				usort($pictoProtos, function ($a, $b) use ($lang) {
					return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
				});
				apcu_store("pictos_prototypes_" . $lang, $pictoProtos);
			}
		} else {
			$pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
			usort($pictoProtos, function ($a, $b) use ($lang) {
				return strcmp($this->translator->trans($a->getLabel(), [], 'game', $lang), $this->translator->trans($b->getLabel(), [], 'game', $lang));
			});
		}
		return $pictoProtos;
	}
}
