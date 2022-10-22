<?php

namespace App\Controller\Soul;

use App\Entity\CitizenRankingProxy;
use App\Entity\Picto;
use App\Entity\SoulResetMarker;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\TwinoidHandler;
use App\Structures\MyHordesConf;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class SoulImportController extends SoulController
{
    /**
     * @Route("jx/soul/import/{code}", name="soul_import")
     * @param TwinoidHandler $twin
     * @param string $code
     * @return Response
     */
    public function soul_import(TwinoidHandler $twin, string $code = ''): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true))
            return $this->redirect($this->generateUrl('soul_settings'));

        $user = $this->getUser();
        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        if ($cache = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user])) {

            $limited = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_LIMITED, false) && (
                $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1) ||
                $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1)
            );

            return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'payload' => $cache->getData($this->entity_manager), 'preview' => true,
                'limited' => $limited,
                'main_soul' => $main !== null && $main->getScope() === $cache->getScope(), 'select_main_soul' => $main === null,
            ]) );

        } else
            $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
            if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
            else $town_cutoff = null;

            $is_limited = $conf->get(MyHordesConf::CONF_IMPORT_LIMITED, false) && (
                    $user->getSoulPoints() > $conf->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1) ||
                    $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $conf->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1)
                );

            $can_reset = $is_limited && $this->entity_manager->getRepository(SoulResetMarker::class)->count(['user' => $user]) === 0;

            return $this->render('ajax/soul/import.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'services' => ['www.hordes.fr' => 'Hordes', 'www.die2nite.com' => 'Die2Nite', 'www.dieverdammten.de' => 'Die Verdammten', 'www.zombinoia.com' => 'Zombinoia'],
                'code' => $code, 'need_sk' => !$twin->hasBuiltInTwinoidAccess(),
                'souls' => $this->entity_manager->getRepository(TwinoidImport::class)->findBy(['user' => $user], ['created' => 'DESC']),
                'select_main_soul' => $main === null,
                'read_only' => $conf->get(MyHordesConf::CONF_IMPORT_READONLY, false),
                'limited_import' => $conf->get(MyHordesConf::CONF_IMPORT_LIMITED, false),
                'limited_import_threshold' => $conf->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1),
                'limited_import_town_threshold' => $conf->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1),
                'is_limited' => $is_limited,
                'can_reset' => $can_reset
            ]));
    }

    /**
     * @Route("jx/soul/import/view/{id}", name="soul_import_viewer")
     * @param int $id
     * @return Response
     */
    public function soul_import_viewer(int $id): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true))
            return $this->redirect($this->generateUrl('soul_settings'));

        $user = $this->getUser();

        $import = $this->entity_manager->getRepository(TwinoidImport::class)->find( $id );
        if (!$import || $import->getUser() !== $user) return $this->redirect($this->generateUrl('soul_import'));

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        $limited = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_LIMITED, false) && (
            $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1) ||
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1)
        );

        return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'payload' => $import->getData($this->entity_manager), 'preview' => false,
            'limited' => $limited,
            'main_soul' => $main !== null && $main->getScope() === $import->getScope(), 'select_main_soul' => $main === null,
        ]) );
    }

    private function validate_twin_json_request(JSONRequestParser $json, TwinoidHandler $twin, ?string &$sc = null, ?string &$sk = null, ?int &$app = null): bool {
        $sc = $json->get('scope');
        if (!in_array($sc, ['www.hordes.fr','www.die2nite.com','www.dieverdammten.de','www.zombinoia.com']))
            return false;

        $sk    = $json->get('sk');
        $app   = (int)$json->get('app');

        if (!$twin->hasBuiltInTwinoidAccess()) {
            if ($app <= 0 || empty($sk))
                return false;
            $twin->setFallbackAccess($app,$sk);
        }

        return true;
    }

    /**
     * @Route("api/soul/import_turl", name="soul_import_turl_api")
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @return Response
     */
    public function soul_import_twinoid_endpoint(JSONRequestParser $json, TwinoidHandler $twin): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true) || $conf->get(MyHordesConf::CONF_IMPORT_READONLY, false))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (!$this->validate_twin_json_request( $json, $twin, $scope ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $request = Request::createFromGlobals();
        return AjaxResponse::success(true, ['goto' => $twin->getTwinoidAuthURL('import#'  . $request->getHost() . $request->getBaseUrl(),$scope)]);
    }

    /**
     * @Route("api/soul/import/{code}", name="soul_import_api")
     * @param string $code
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @param LoggerInterface $logger
     * @return Response
     */
    public function soul_import_loader(string $code, JSONRequestParser $json, TwinoidHandler $twin, LoggerInterface $logger): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true) || $conf->get(MyHordesConf::CONF_IMPORT_READONLY, false))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->validate_twin_json_request( $json, $twin, $scope ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $twin->setCode( $code );

        $data1 = $twin->getData("$scope/tid",'me', [
            'name','twinId',
            'playedMaps' => [ 'mapId','survival','mapName','season','v1','score','dtype','msg','comment','cleanup' ]
        ], $error, $raw_data);

        if ($error || isset($data1['error'])) {
            $logger->alert( 'Twinoid import failed at stage 1.', [ 'raw' => $raw_data, 'error' => $error ] );
            if (isset($data1['error']) && $data1['error'] === 'server_error')
                return AjaxResponse::error(self::ErrorETwinImportServerCrash, ['response' => $data1]);
            else return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data1]);
        }

        $twin_id = (int)($data1['twinId'] ?? 0);
        if (!$twin_id) {
            $logger->alert( 'Twinoid import failed at stage 2.', [ 'raw' => $raw_data, 'error' => 'no_twin_id' ] );
            return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data1]);
        }

        $data2 = $twin->getData('twinoid.com',"site?host={$scope}", [
            'me' => [ 'points','npoints',
                'stats' => [ 'id','score','name','rare','social' ],
                'achievements' => [ 'id','name','stat','score','points','npoints','date','index',
                    'data' => ['type','title','url','prefix','suffix']
                ]
            ]
        ], $error, $raw_data);

        if ($error || isset($data2['error'])) {
            $logger->alert( 'Twinoid import failed at stage 3.', [ 'raw' => $raw_data, 'error' => $error ] );
            return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data2]);
        }

        if ($user->getTwinoidID() === null) {

            if (
                $this->entity_manager->getRepository(User::class)->findOneBy(['twinoidID' => $twin_id]) ||
                $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['twinoidID' => $twin_id])
            ) return AjaxResponse::error(self::ErrorTwinImportProfileInUse);

        } elseif ($user->getTwinoidID() !== $twin_id)
            return AjaxResponse::error(self::ErrorTwinImportProfileMismatch);

        $import_preview = (new TwinoidImportPreview())
            ->setUser($user)
            ->setTwinoidID($twin_id)
            ->setCreated(new DateTime())
            ->setScope($scope)
            ->setPayload(array_merge($data1,$data2['me']));

        try {
            $this->entity_manager->persist($import_preview);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/import-cancel", name="soul_import_cancel_api")
     * @return Response
     */
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
     * @Route("api/soul/import-confirm/{id}", name="soul_import_confirm_api")
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @param int $id
     * @return Response
     */
    public function soul_import_confirm(JSONRequestParser $json, TwinoidHandler $twin, int $id = -1): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true) || $conf->get(MyHordesConf::CONF_IMPORT_READONLY, false))
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

        $limit = $conf->get(MyHordesConf::CONF_IMPORT_LIMITED, false);
        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        if ($limit &&
            $user->getSoulPoints() <= $conf->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1) &&
            $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) <= $conf->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1)
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

                $this->user_handler->computePictoUnlocks($user);
                $this->entity_manager->persist($user);
                $this->entity_manager->flush();

            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException );
            }

            return AjaxResponse::success();
        } else return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

    /**
     * @Route("api/soul/import-soft-reset", name="soul_import_soft_reset_api")
     * @return Response
     */
    public function soul_soft_reset(): Response
    {
        $conf = $this->conf->getGlobalConf();

        if (!$conf->get(MyHordesConf::CONF_IMPORT_ENABLED, true) || $conf->get(MyHordesConf::CONF_IMPORT_READONLY, false))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $user = $this->getUser();

        $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
        if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
        else $town_cutoff = null;

        $limited = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_LIMITED, false) && (
                $user->getSoulPoints() > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1) ||
                $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($user, $town_cutoff, true) > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1)
            );

        $can_reset = $limited && $this->entity_manager->getRepository(SoulResetMarker::class)->count(['user' => $user]) === 0;
        if (!$can_reset) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        /** @var CitizenRankingProxy $ranking */
        foreach ($this->entity_manager->getRepository(CitizenRankingProxy::class)->getNonAlphaTowns($user, $town_cutoff, false) as $ranking)
            if (!$ranking->hasDisableFlag(CitizenRankingProxy::DISABLE_ALL) && !$ranking->getTown()->getImported()) {
                $this->entity_manager->persist($ranking->addDisableFlag(CitizenRankingProxy::DISABLE_ALL));
                foreach ($this->entity_manager->getRepository(Picto::class)->findBy(['townEntry' => $ranking->getTown(), 'user' => $user]) as $picto)
                    $this->entity_manager->persist( $picto->setDisabled(true) );
                $this->entity_manager->persist( (new SoulResetMarker())->setUser( $user )->setRanking( $ranking ) );
            }

        try {
            $this->entity_manager->flush();
            $user->setSoulPoints( $this->user_handler->fetchSoulPoints( $user, false ) );
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

            $this->user_handler->computePictoUnlocks($user);
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
