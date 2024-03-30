<?php
/** @noinspection PhpRouteMissingInspection */

namespace App\Controller\API;

use App\Annotations\GateKeeperProfile;
use App\Controller\InventoryAwareController;
use App\Entity\User;
use App\Enum\ExternalAPIError;
use App\Service\ActionHandler;
use App\Service\AdminHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\DoctrineCacheService;
use App\Service\EventProxyService;
use App\Service\GazetteService;
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\User\PictoService;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CoreController
 * @package App\Controller\API
 */
#[GateKeeperProfile(allow_during_attack: true, record_user_activity: false)]
abstract class CoreController extends InventoryAwareController {

    protected GazetteService         $gazette_service;
    protected AdminHandler           $adminHandler;
    protected UrlGeneratorInterface  $urlGenerator;
    protected array $languages;

    protected $pictoService;

    /**
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param ActionHandler $ah
     * @param TimeKeeperService $tk
     * @param DeathHandler $dh
     * @param PictoHandler $ph
     * @param TranslatorInterface $translator
     * @param RandomGenerator $rg
     * @param LogTemplateHandler $lh
     * @param ConfMaster $conf
     * @param ZoneHandler $zh
     * @param UserHandler $uh
     * @param CrowService $armbrust
     * @param Packages $a
     * @param TownHandler $th
     * @param GazetteService $gs
     * @param AdminHandler $adminHandler
     * @param UrlGeneratorInterface $urlGenerator
     * @param DoctrineCacheService $doctrineCache
     * @param EventProxyService $events
     * @param HookExecutor $hookExecutor
     * @param PictoService $pictoService
     */
    public function __construct(EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch,
                                ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh,
                                PictoHandler $ph, TranslatorInterface $translator,
                                RandomGenerator $rg, LogTemplateHandler $lh,
                                ConfMaster $conf, ZoneHandler $zh, UserHandler $uh,
                                CrowService $armbrust, Packages $a, TownHandler $th, GazetteService $gs,
                                AdminHandler $adminHandler, UrlGeneratorInterface $urlGenerator, DoctrineCacheService $doctrineCache, EventProxyService $events, HookExecutor $hookExecutor,
                                PictoService $pictoService
    ) {
        parent::__construct($em, $ih, $ch, $ah, $dh, $ph, $translator, $lh, $tk, $rg, $conf, $zh, $uh, $armbrust, $th, $a, $doctrineCache, $events, $hookExecutor);
        $this->gazette_service = $gs;
        $this->adminHandler = $adminHandler;
        $this->urlGenerator = $urlGenerator;
        $this->languages = $this->generatedLangsCodes;
        $this->pictoService = $pictoService;
    }

    abstract public function on_error( ExternalAPIError $message, string $language ): Response;

    protected function getRequestLanguage(Request $request, ?User $user = null): string {
        $language =
            $request->query->get('lang') ??
            $request->request->get('lang') ??
            $user?->getLanguage() ??
            'de';

        $language = explode('_', $language)[0];

        if ($language !== 'all' && !in_array($language, $this->generatedLangsCodes)) {
            $language = 'de';
        }

        return $language;
    }

    protected function getIconPath(string $fullPath): string {
        $list = explode('/build/images/', $fullPath, 2);
        return count($list) === 2 ? $list[1] : $fullPath;
    }
}
