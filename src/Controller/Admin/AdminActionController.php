<?php

namespace App\Controller\Admin;

use App\Controller\CustomAbstractController;
use App\Entity\AttackSchedule;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\LogEntryTemplate;
use App\Entity\User;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\ZoneHandler;
use App\Structures\BankItem;
use App\Translation\T;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminActionController extends CustomAbstractController
{
    protected $logTemplateHandler;
    protected $zone_handler;

    public static function getAdminActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),  'id' => 0],
            ['name' => T::__('Users', 'admin'),      'id' => 1],
            ['name' => T::__('Foren-Mod.', 'admin'),  'id' => 2],
            ['name' => T::__('Städte', 'admin'),     'id' => 3],
            ['name' => T::__('Zukunft', 'admin'),    'id' => 4],
            ['name' => T::__('AntiSpam', 'admin'),   'id' => 5],
            ['name' => T::__('Apps', 'admin'),   'id' => 6],
        ];
    }

    public function __construct(EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $lth, TranslatorInterface $translator, ZoneHandler $zh, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->logTemplateHandler = $lth;
        $this->zone_handler = $zh;

    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null, $locale = null): array
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
            case 0: return $this->redirect($this->generateUrl('admin_dashboard'));
            case 1: return $this->redirect($this->generateUrl('admin_users'));
            case 2: return $this->redirect($this->generateUrl('admin_reports'));
            case 3: return $this->redirect($this->generateUrl('admin_town_list'));
            case 4: return $this->redirect($this->generateUrl('admin_changelogs'));
            case 5: return $this->redirect($this->generateUrl('admin_spam_domain_view'));
            case 6: return $this->redirect($this->generateUrl('admin_app_view'));
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

    protected function renderInventoryAsBank( Inventory $inventory ) {
        $qb = $this->entity_manager->createQueryBuilder();
        $qb
            ->select('i.id', 'c.label as l1', 'cr.label as l2', 'SUM(i.count) as n')->from('App:Item','i')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory);
        $qb->groupBy('i.prototype', 'i.broken');
        $qb
            ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
            ->leftJoin('App:ItemCategory', 'c', Join::WITH, 'p.category = c.id')
            ->leftJoin('App:ItemCategory', 'cr', Join::WITH, 'c.parent = cr.id')
            ->addOrderBy('c.ordering','ASC')
            ->addOrderBy('p.id', 'ASC')
            ->addOrderBy('i.id', 'ASC');

        $data = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $final = [];
        $cache = [];

        foreach ($data as $entry) {
            $label = $entry['l2'] ?? $entry['l1'] ?? 'Sonstiges';
            if (!isset($final[$label])) $final[$label] = [];
            $final[$label][] = [ $entry['id'], $entry['n'] ];
            $cache[] = $entry['id'];
        }

        $item_list = $this->entity_manager->getRepository(Item::class)->findAllByIds($cache);
        foreach ( $final as $label => &$entries )
            $entries = array_map(function( array $entry ) use (&$item_list): BankItem { return new BankItem( $item_list[$entry[0]], $entry[1] ); }, $entries);

        return $final;
    }
}
