<?php

namespace App\Controller\Admin;

use App\Entity\AntiSpamDomains;
use App\Entity\Changelog;
use App\Entity\ExternalApp;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\UserFactory;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Imagick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminAppController extends AdminActionController
{
    /**
     * @Route("jx/admin/apps/all", name="admin_app_view")
     * @return Response
     */
    public function ext_app_view(): Response
    {
        $apps = $this->entity_manager->getRepository(ExternalApp::class)->findAll();
        return $this->render( 'ajax/admin/apps/list.html.twig', ['apps' => $apps]);
    }

    /**
     * @Route("jx/admin/apps/{id<\d+>}", name="admin_app_edit")
     * @param int $id
     * @return Response
     */
    public function ext_app_edit(int $id): Response
    {
        T::__("Neue Anwendung registrieren", "admin");
        T::__("Änderungen an '%appname%' speichern", "admin");
        if (!$this->isGranted('ROLE_ADMIN')) $this->redirect($this->generateUrl('admin_app_view'));
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);
        if ($app === null) return $this->redirect($this->generateUrl('admin_app_view'));
        return $this->render( 'ajax/admin/apps/edit.html.twig', ['current_app' => $app]);
    }

    /**
     * @Route("jx/admin/apps/new", name="admin_app_new")
     * @return Response
     */
    public function ext_app_new(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) $this->redirect($this->generateUrl('admin_app_view'));
        return $this->render( 'ajax/admin/apps/edit.html.twig', ['current_app' => null]);
    }

    /**
     * @Route("api/admin/apps/toggle/{id<\d+>}", name="admin_toggle_ext_app")
     * @param int $id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function ext_app_toggle(int $id, JSONRequestParser $parser): Response {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);
        if ($app === null ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $app->setActive( (bool)$parser->get('on', true) );

        $this->entity_manager->persist($app);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/apps/register/{id<-?\d+>}", name="admin_update_ext_app")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param RandomGenerator $rand
     * @return Response
     */
    public function ext_app_update(int $id, JSONRequestParser $parser, RandomGenerator $rand): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['name','owner','contact','url'], true)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $app = $id < 0 ? new ExternalApp() : $this->entity_manager->getRepository(ExternalApp::class)->find($id);
        if ($app === null ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $owner = null;
        if ((int)$parser->get('owner') > 0) {
            $owner = $this->entity_manager->getRepository(User::class)->find((int)$parser->get('owner'));
            if ($owner === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $app
            ->setName( $parser->get('name') )
            ->setActive( $id < 0 ? true : $app->getActive() )
            ->setContact( $parser->get('contact') )
            ->setUrl( $parser->get('url') )
            ->setOwner($owner)
            ->setTesting( (bool)$parser->get('test') )
            ->setLinkOnly( !(bool)$parser->get('flux') );

        if ($parser->get('icon') !== false) {
            if ($parser->get('icon') === null)
                $app->setImage(null)->setImageName(null)->setImageFormat(null);
            else {
                $payload = $parser->get_base64('icon');

                if (strlen( $payload ) > 3145728) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                if (!extension_loaded('imagick')) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

                $im_image = new Imagick();

                try {
                    if (!$im_image->readImageBlob($payload)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    if (!in_array($im_image->getImageFormat(), ['GIF','JPEG','BMP','PNG','WEBP'])) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                    if ($im_image->getImageFormat() === 'GIF') {
                        $im_image->coalesceImages();
                        $im_image->resetImagePage('0x0');
                        $im_image->setFirstIterator();
                    }

                    $w = $im_image->getImageWidth();
                    $h = $im_image->getImageHeight();

                    if ( ($w !== 16 || $h !== 16) && !$im_image->resizeImage(16,16,imagick::FILTER_SINC, 1, true )) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

                    if ($im_image->getImageFormat() === 'GIF') $im_image->setFirstIterator();

                    switch ($im_image->getImageFormat()) {
                        case 'JPEG':
                            $im_image->setImageCompressionQuality ( 100 );
                            break;
                        case 'PNG':
                            $im_image->setOption('png:compression-level', 9);
                            break;
                        case 'GIF':
                            $im_image->setOption('optimize', true);
                            break;
                        default: break;
                    }
                    $processed_image_data = $im_image->getImagesBlob();
                    $app->setImage($processed_image_data)->setImageName(md5($processed_image_data))->setImageFormat( strtolower( $im_image->getImageFormat() ) );
                } catch (\Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorInternalError, ['msg' => $e->getMessage(), 'line' => $e->getLine()] );
                }
            }
        }

        if ($app->getLinkOnly()) $app->setSecret(null);
        elseif ( empty($app->getSecret()) || (bool)$parser->get('rekey') ) {
            $s = '';
            for ($i = 0; $i < 32; $i++) $s .= $rand->pick(['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f']);
            $app->setSecret( $s );
        }

        $this->entity_manager->persist($app);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
