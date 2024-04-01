<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractController;
use App\Entity\MarketingCampaign;
use App\Entity\OfficialGroup;
use App\Entity\OfficialGroupMessageLink;
use App\Entity\Season;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\Media\ImageService;
use App\Service\PermissionHandler;
use App\Service\RandomGenerator;
use App\Structures\MyHordesConf;
use App\Translation\T;
use Exception;
use ReflectionClass;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminCampaignController extends CustomAbstractController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/cst/campaigns', name: 'admin_campaigns')]
    public function campaign_view(): Response
    {
        $campaigns = $this->entity_manager->getRepository(MarketingCampaign::class)->findAll();

        if (!$this->isGranted('ROLE_ADMIN')) {
            $campaigns = array_filter($campaigns, fn(MarketingCampaign $c) => $c->getManagers()->contains($this->getUser()));
            if (empty($campaigns)) return new Response('', 403);
        }


        return $this->render( 'ajax/admin/campaigns/list.html.twig', $this->addDefaultTwigArgs(null, [
            'campaigns' => $campaigns,
        ]));
    }

    /**
     * @param bool $new
     * @param MarketingCampaign|null $campaign
     * @return Response
     */
    #[Route(path: 'jx/admin/cst/campaigns/new', name: 'admin_campaign_new', defaults: ['new' => true], priority: 1)]
    #[Route(path: 'jx/admin/cst/campaigns/{id}', name: 'admin_campaign_edit', defaults: ['new' => false], priority: 0)]
    public function campaign_new(bool $new, ?MarketingCampaign $campaign): Response
    {
        if (
            ($new && !$this->isGranted('ROLE_ANIMAC')) ||
            (!$new && !$campaign)
        ) $this->redirectToRoute('admin_campaigns');

        return $this->render( 'ajax/admin/campaigns/edit.html.twig', $this->addDefaultTwigArgs(null, [
            'current_campaign' => $campaign,
        ]));
    }

    /**
     * @param bool $new
     * @param MarketingCampaign|null $campaign
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/cst/campaigns/new', name: 'admin_campaign_create', defaults: ['new' => true], priority: 1)]
    #[Route(path: 'api/admin/cst/campaigns/{id}', name: 'admin_campaign_update', defaults: ['new' => false], priority: 0)]
    public function update_campaign(bool $new, ?MarketingCampaign $campaign, JSONRequestParser $parser): Response
    {
        if (
            ($new && !$this->isGranted('ROLE_ANIMAC')) ||
            (!$new && !$campaign)
        ) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['name','slug'], true)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($new) $campaign = new MarketingCampaign();

        $slug = strip_tags( $parser->trimmed('slug') );
        // Replace non letter or digits by -
        $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
        // Transliterate
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
        // Remove unwanted characters
        $slug = preg_replace('~[^-\w]+~', '', $slug);
        // Trim
        $slug = trim($slug, '-');
        // Remove duplicate -
        $slug = preg_replace('~-+~', '-', $slug);
        // Lowercase
        $slug = strtolower($slug);
        // Check if it is empty
        if (empty($slug)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $campaign
            ->setName( $parser->trimmed('name') )
            ->setSlug( $slug );

        if ($this->isGranted('ROLE_ANIMAC')) {
            $am = $parser->get_array('m_add');
            $rm = $parser->get_array('m_rem');

            foreach ($am as $add_id) {

                if (in_array($add_id, $rm)) continue;

                $target_user = $this->entity_manager->getRepository(User::class)->find((int)$add_id);
                if (!$target_user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                if ($campaign->getManagers()->contains($target_user)) continue;
                $campaign->addManager($target_user);
            }

            foreach ($rm as $rem_id) {
                $target_user = $this->entity_manager->getRepository(User::class)->find((int)$rem_id);
                if (!$target_user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                $campaign->removeManager($target_user);
            }
        }

        try {
            $this->entity_manager->persist($campaign);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param MarketingCampaign $campaign
     * @return Response
     */
    #[Route(path: 'api/admin/cst/campaigns/delete/{id}', name: 'admin_campaign_delete')]
    public function delete_campaign(MarketingCampaign $campaign): Response
    {
        if (!$this->isGranted('ROLE_ANIMAC'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        try {
            $this->entity_manager->remove($campaign);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }
}