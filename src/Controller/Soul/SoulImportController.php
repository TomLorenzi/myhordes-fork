<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller\Soul;

use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\TwinoidHandler;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
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
        if ($this->getUser()->getShadowBan()) return $this->redirect($this->generateUrl( 'soul_disabled' ));

        $user = $this->getUser();
        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        if ($cache = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user])) {

            return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'payload' => $cache->getData($this->entity_manager), 'preview' => true,
                'main_soul' => $main !== null && $main->getScope() === $cache->getScope(), 'select_main_soul' => $main === null,
            ]) );

        } else return $this->render( 'ajax/soul/import.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'services' => ['www.hordes.fr' => 'Hordes','www.die2nite.com' => 'Die2Nite','www.dieverdammten.de' => 'Die Verdammten','www.zombinoia.com' => 'Zombinoia'],
            'code' => $code, 'need_sk' => !$twin->hasBuiltInTwinoidAccess(),
            'souls' => $this->entity_manager->getRepository(TwinoidImport::class)->findBy(['user' => $user], ['created' => 'DESC']),
            'select_main_soul' => $main === null
        ]) );
    }

    /**
     * @Route("jx/soul/import/view/{id}", name="soul_import_viewer")
     * @param int $id
     * @return Response
     */
    public function soul_import_viewer(int $id): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return $this->redirect($this->generateUrl( 'soul_disabled' ));

        $import = $this->entity_manager->getRepository(TwinoidImport::class)->find( $id );
        if (!$import || $import->getUser() !== $user) return $this->redirect($this->generateUrl('soul_import'));

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'payload' => $import->getData($this->entity_manager), 'preview' => false,
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
        if (!$this->validate_twin_json_request( $json, $twin, $scope ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        return AjaxResponse::success(true, ['goto' => $twin->getTwinoidAuthURL('import',$scope)]);
    }

    /**
     * @Route("api/soul/import/{code}", name="soul_import_api")
     * @param string $code
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @return Response
     */
    public function soul_import_loader(string $code, JSONRequestParser $json, TwinoidHandler $twin, LoggerInterface $logger): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

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
            return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data1]);
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

        $user->setTwinoidImportPreview( (new TwinoidImportPreview())
            ->setTwinoidID($twin_id)
            ->setCreated(new DateTime())
            ->setScope($scope)
            ->setPayload(array_merge($data1,$data2['me'])) );

        try {
            $this->entity_manager->persist($user);
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

        $user->setTwinoidImportPreview(null);
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
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

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

        if ($twin->importData( $user, $scope, $data, $to_main )) {

            if ($id < 0) {
                $import_ds = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'scope' => $scope]);
                if ($import_ds === null) $user->addTwinoidImport( $import_ds = new TwinoidImport() );

                $import_ds->fromPreview( $pending );
                $import_ds->setMain( $to_main );

                $user->setTwinoidID( $pending->getTwinoidID() );
                $user->setTwinoidImportPreview(null);
                $pending->setUser(null);

                $this->entity_manager->remove($pending);
            } else $selected->setMain($to_main);

            $this->entity_manager->persist( $user );

            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()]);
            }

            return AjaxResponse::success();
        } else return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

}
