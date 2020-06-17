<?php

namespace App\Controller\Admin;

use App\Entity\AttackSchedule;
use App\Entity\LogEntryTemplate;
use App\Entity\User;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminActionController extends AbstractController
{
    protected $entity_manager;
    protected $logTemplateHandler;
    protected $conf;
    protected $translator;


    public static function getAdminActions(): array {
        return [
            ['name' => T::__('Users', 'admin'),     'id' => 1],
            ['name' => T::__('Meldungen', 'admin'), 'id' => 2],
            ['name' => T::__('Städte', 'admin'),    'id' => 3],
            ['name' => T::__('Zukunft', 'admin'),   'id' => 4],
        ];
    }

    public function __construct(EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $lth, TranslatorInterface $translator)
    {
        $this->entity_manager = $em;
        $this->conf = $conf;
        $this->logTemplateHandler = $lth;
        $this->translator = $translator;

    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array
    {
        $data = $data ?? [];

        $data["admin_tab"] = $section;

        return $data;
    }

    protected function renderLog( ?int $day, $town, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];
        /** @var TownLogEntry $entity */
        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter($town, $day, null, $zone, $type, $max ) as $idx => $entity) {
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

                $variableTypes = $template->getVariableTypes();
                $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);

                try {
                    $entries[$idx]['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (Exception $e) {
                    $entries[$idx]['text'] = "null";
                }             
            }

        // $entries = array($entity->find($id), $entity->find($id)->findRelatedEntity());

        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $entries,
            'canHideEntry' => false,
        ] );
    }

    /**
     * @Route("jx/admin/dash", name="admin_dashboard")
     * @return Response
     */
    public function dash(): Response
    {
        return $this->render( 'ajax/admin/dash.html.twig', [
            'actions' => self::getAdminActions(),
            'now' => time(),
            'schedules' => $this->isGranted('ROLE_ADMIN') ? $this->entity_manager->getRepository(AttackSchedule::class)->findByCompletion( false ) : [],
        ]);
    }

    /**
     * @Route("admin/log/{a}", name="admin_log", condition="!request.isXmlHttpRequest()")
     * @param ParameterBagInterface $params
     * @param string $a
     * @return Response
     */
    public function log(ParameterBagInterface $params, string $a = ''): Response
    {
        $dispo = 'attachment';
        if ($a === 'view') $dispo = 'inline';

        return $this->file($params->get('kernel.project_dir') . '/var/log/' . $params->get('kernel.environment') . '.log', 'myhordes.log', $dispo);
    }

    /**
     * @Route("api/admin/clearlog", name="api_admin_clear_log")
     * @param ParameterBagInterface $params
     * @return Response
     */
    public function clear_log_api(ParameterBagInterface $params): Response
    {

        $f = $params->get('kernel.project_dir') . '/var/log/' . $params->get('kernel.environment') . '.log';
        if (file_exists( $f )) {
            $ff = fopen($f, "w+");
            fclose($ff);
        }

        return AjaxResponse::success();
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
        if (!$user || !$user->getValidated() || $user->getRightsElevation() < User::ROLE_CROW) {
            $ts->setToken();
            return new AjaxResponse( ['success' => false ] );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/admin/action/{id}", name="admin_action", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function index(int $id): Response
    {
        switch ($id) {
            case 1: return $this->redirect($this->generateUrl('admin_users'));
            case 2: return $this->redirect($this->generateUrl('admin_reports'));
            case 3: return $this->redirect($this->generateUrl('admin_town_list'));
            case 4: return $this->redirect($this->generateUrl('admin_changelogs'));
            default: break;
        }
        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }

    /**
     * @Route("api/admin/raventimes/log", name="admin_newspaper_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_newspaper_api(JSONRequestParser $parser): Response {
        $town_id = $parser->get('town', -1);
        $town = $this->entity_manager->getRepository(Town::class)->find($town_id);
        return $this->renderLog((int)$parser->get('day', -1), $town, false, null, null);
    }
}
