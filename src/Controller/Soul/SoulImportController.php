<?php

namespace App\Controller\Soul;

use App\Entity\CitizenRankingProxy;
use App\Entity\Picto;
use App\Entity\SoulResetMarker;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\JSONRequestParser;
use App\Service\TwinoidHandler;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest() || request.headers.get("Accept") === "application/json"')]
class SoulImportController extends SoulController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/import', name: 'soul_import')]
    public function soul_import(): Response
    {
        $conf = $this->conf->getGlobalConf();

        if ($this->getUser()->getTwinoidID() === null || !$conf->get(MyHordesSetting::SoulImportEnabled))
            return $this->redirect($this->generateUrl('soul_settings'));

        $user = $this->getUser();
        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwCutoff);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        if ($cache = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user])) {

            $limited = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitsActive) && (
                $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitSpThreshold) ||
                $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwThreshold)
            );

            return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'payload' => $cache->getData($this->entity_manager), 'preview' => true,
                'limited' => $limited,
                'main_soul' => $main !== null && $main->getScope() === $cache->getScope(), 'select_main_soul' => $main === null,
            ]) );

        } else
            $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwCutoff);
            if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
            else $town_cutoff = null;

            $is_limited = $conf->get(MyHordesSetting::SoulImportLimitsActive) && (
                    $user->getSoulPoints() > $conf->get(MyHordesSetting::SoulImportLimitSpThreshold) ||
                    $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $conf->get(MyHordesSetting::SoulImportLimitTwThreshold)
                );

            $can_reset = $is_limited && $this->entity_manager->getRepository(SoulResetMarker::class)->count(['user' => $user]) === 0;

            return $this->render('ajax/soul/import.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'services' => ['www.hordes.fr' => 'Hordes', 'www.die2nite.com' => 'Die2Nite', 'www.dieverdammten.de' => 'Die Verdammten', 'www.zombinoia.com' => 'Zombinoia'],
                'souls' => $this->entity_manager->getRepository(TwinoidImport::class)->findBy(['user' => $user], ['created' => 'DESC']),
                'select_main_soul' => $main === null,
                'read_only' => $conf->get(MyHordesSetting::SoulImportReadOnly),
                'limited_import' => $conf->get(MyHordesSetting::SoulImportLimitsActive),
                'limited_import_threshold' => $conf->get(MyHordesSetting::SoulImportLimitSpThreshold),
                'limited_import_town_threshold' => $conf->get(MyHordesSetting::SoulImportLimitTwThreshold),
                'is_limited' => $is_limited,
                'can_reset' => $can_reset
            ]));
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/soul/import/view/{id}', name: 'soul_import_viewer')]
    public function soul_import_viewer(int $id): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesSetting::SoulImportEnabled))
            return $this->redirect($this->generateUrl('soul_settings'));

        $user = $this->getUser();

        $import = $this->entity_manager->getRepository(TwinoidImport::class)->find( $id );
        if (!$import || $import->getUser() !== $user) return $this->redirect($this->generateUrl('soul_import'));

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwCutoff);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        $limited = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitsActive) && (
            $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitSpThreshold) ||
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwThreshold)
        );

        return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'payload' => $import->getData($this->entity_manager), 'preview' => false,
            'limited' => $limited,
            'main_soul' => $main !== null && $main->getScope() === $import->getScope(), 'select_main_soul' => $main === null,
        ]) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/soul/import-cancel', name: 'soul_import_cancel_api')]
    public function soul_import_cancel(): Response
    {
        $user = $this->getUser();

        $pending = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]);
        if (!$pending) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $pending->setUser(null);

        try {
            $this->entity_manager->remove($pending);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @param int $id
     * @return Response
     * @throws Exception
     */
    #[Route(path: 'api/soul/import-confirm/{id}', name: 'soul_import_confirm_api')]
    public function soul_import_confirm(JSONRequestParser $json, TwinoidHandler $twin, EventProxyService $proxy, int $id = -1): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesSetting::SoulImportEnabled) || $conf->get(MyHordesSetting::SoulImportReadOnly))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $user = $this->getUser();

        $to_main = (bool)$json->get('main', false);
        $pending = null; $selected = null;

        if ($id < 0) {
            $pending = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]);
            if (!$pending) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $scope = $pending->getScope();
            $data = $pending->getData($this->entity_manager);
        } else {
            $selected = $this->entity_manager->getRepository(TwinoidImport::class)->find($id);
            if (!$selected || $selected->getUser() !== $user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $scope = $selected->getScope();
            $data = $selected->getData($this->entity_manager);
        }

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);
        if ($main !== null) {
            if ($main->getScope() !== $scope && $to_main)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            elseif ($main->getScope() === $scope) $to_main = true;
        }

        $limit = $conf->get(MyHordesSetting::SoulImportLimitsActive);
        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwCutoff);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        if ($limit &&
            $user->getSoulPoints() <= $conf->get(MyHordesSetting::SoulImportLimitSpThreshold) &&
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) <= $conf->get(MyHordesSetting::SoulImportLimitTwThreshold)
        )
            $limit = false;

        if ($twin->importData( $user, $scope, $data, $to_main, $limit )) {

            if ($id < 0) {
                $import_ds = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'scope' => $scope]);
                if ($import_ds === null) $user->addTwinoidImport( $import_ds = new TwinoidImport() );

                $import_ds->fromPreview( $pending );
                $import_ds->setMain( $to_main );

                $user->setTwinoidID( $pending->getTwinoidID() );
                $pending->setUser(null);

                $this->entity_manager->remove($pending);
            } else $selected->setMain($to_main);

                $this->entity_manager->persist( $user );

            try {
                $this->entity_manager->flush();
                $user->setImportedSoulPoints( $this->user_handler->fetchImportedSoulPoints( $user ) );
                $this->entity_manager->persist($user);
                $this->entity_manager->flush();

                $proxy->pictosPersisted( $user, imported: true );
                $this->entity_manager->persist($user);
                $this->entity_manager->flush();

            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException );
            }

            return AjaxResponse::success();
        } else return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

    /**
     * @return Response
     * @throws Exception
     */
    #[Route(path: 'api/soul/import-soft-reset', name: 'soul_import_soft_reset_api')]
    public function soul_soft_reset(EventProxyService $proxy): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesSetting::SoulImportEnabled) || $conf->get(MyHordesSetting::SoulImportReadOnly))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $user = $this->getUser();

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwCutoff);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        $limited = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitsActive) && (
                $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitSpThreshold) ||
                $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesSetting::SoulImportLimitTwThreshold)
            );

        $can_reset = $limited && $this->entity_manager->getRepository(SoulResetMarker::class)->count(['user' => $user]) === 0;
        if (!$can_reset) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        /** @var CitizenRankingProxy $ranking */
        foreach ($this->entity_manager->getRepository(CitizenRankingProxy::class)->getNonAlphaTowns($user, $town_cutoff, false) as $ranking)
            if (!$ranking->hasDisableFlag(CitizenRankingProxy::DISABLE_ALL) && !$ranking->getTown()->getImported()) {
                $this->entity_manager->persist($ranking->addDisableFlag(CitizenRankingProxy::DISABLE_ALL));
                foreach ($this->entity_manager->getRepository(Picto::class)->findBy(['townEntry' => $ranking->getTown(), 'user' => $user]) as $picto)
                    if (!$picto->isManual())
                        $this->entity_manager->persist( $picto->setDisabled(true) );
                $this->entity_manager->persist( (new SoulResetMarker())->setUser( $user )->setRanking( $ranking ) );
            }

        try {
            $this->entity_manager->flush();
            $user->setSoulPoints( $this->user_handler->fetchSoulPoints( $user, false ) );
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

            $proxy->pictosPersisted( $user, imported: true );
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
