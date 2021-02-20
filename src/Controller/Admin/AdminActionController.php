<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
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
use DirectoryIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
 */
class AdminActionController extends CustomAbstractController
{
    protected $logTemplateHandler;
    protected $zone_handler;

    public static function getAdminActions(): array {
        return [
            ['name' => T::__('Dashboard', 'admin'),   'id' => 0, 'route' => 'admin_dashboard'],
            ['name' => T::__('Users', 'admin'),       'id' => 1, 'route' => 'admin_users'],
            ['name' => T::__('Foren-Mod.', 'admin'),  'id' => 2, 'route' => 'admin_reports'],
            ['name' => T::__('Städte', 'admin'),      'id' => 3, 'route' => 'admin_town_list'],
            ['name' => T::__('Zukunft', 'admin'),     'id' => 4, 'route' => 'admin_changelogs'],
            ['name' => T::__('AntiSpam', 'admin'),    'id' => 5, 'route' => 'admin_spam_domain_view'],
            ['name' => T::__('Apps', 'admin'),        'id' => 6, 'route' => 'admin_app_view'],
            ['name' => T::__('Saisons', 'admin'),     'id' => 7, 'route' => 'admin_seasons_view'],
        ];
    }

    public function __construct(EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $lth, TranslatorInterface $translator, ZoneHandler $zh, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->logTemplateHandler = $lth;
        $this->zone_handler = $zh;

    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array
    {
        $data = parent::addDefaultTwigArgs($section, $data);

        $data["admin_tab"] = $section;

        return $data;
    }

    protected function renderLog( ?int $day, $town, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];
        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter($town, $day, null, $zone, $type, $max, null ) as $idx => $entity) {
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

        return $this->render( 'ajax/admin/towns/log_content.html.twig', [
            'entries' => $entries,
            'canHideEntry' => false,
        ] );
    }

    /**
     * @Route("jx/admin/dash", name="admin_dashboard")
     * @param ParameterBagInterface $params
     * @return Response
     */
    public function dash(ParameterBagInterface $params): Response
    {
        $log_files = [];
        $base_dir = "{$params->get('kernel.project_dir')}/var/log";
        $log_paths = [$base_dir];

        while (!empty($log_paths)) {
            $path = array_pop($log_paths);
            foreach (new DirectoryIterator($path) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isDot() || $fileInfo->isLink()) continue;
                elseif ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'log')
                    $log_files[] = str_replace(['/','\\'],'::', str_replace("$base_dir/",'', $fileInfo->getRealPath()));
                elseif ($fileInfo->isDir()) $log_paths[] = $fileInfo->getRealPath();
            }
        }

        sort($log_files);

        return $this->render( 'ajax/admin/dash.html.twig', $this->addDefaultTwigArgs(null, [
            'logs' => $log_files,
            'actions' => self::getAdminActions(),
            'now' => time(),
            'schedules' => $this->isGranted('ROLE_ADMIN') ? $this->entity_manager->getRepository(AttackSchedule::class)->findByCompletion( false ) : [],
        ]));
    }

    /**
     * @Route("admin/log/{a}/{f}", name="admin_log", condition="!request.isXmlHttpRequest()")
     * @param ParameterBagInterface $params
     * @param string $a
     * @param string $f
     * @return Response
     */
    public function log(ParameterBagInterface $params, string $a = '', string $f = ''): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return new Response('', 403);

        if (empty($f)) $f = $params->get('kernel.environment');
        $f = str_replace(['..','::'],['','/'],$f);

        $path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/log/{$f}");
        if (!$path->isFile() || !strtolower($path->getExtension()) === 'log') return new Response('', 404);

        if ($path->getSize() > 67108864)        $a = 'download';
        else if ($path->getSize() > 16777216 && $a === 'view') $a = 'print';

        switch ($a) {
            case 'view': return $this->render( 'web/logviewer.html.twig', [
                'filename' => $path->getRealPath(),
                'log' => file_get_contents($path->getRealPath()),
            ] );
            case 'print': return $this->file($path->getRealPath(), $path->getFilename(), 'inline');
            case 'download': return $this->file($path->getRealPath(), $path->getFilename(), 'attachment');
            default: return new Response('', 403);
        }
    }

    /**
     * @Route("api/admin/clearlog/{f}", name="api_admin_clear_log")
     * @param ParameterBagInterface $params
     * @param string $f
     * @return Response
     */
    public function clear_log_api(ParameterBagInterface $params, string $f = ''): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (empty($f)) $f = $params->get('kernel.environment');
        $f = str_replace(['..','::'],['','/'],$f);

        $path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/log/{$f}");
        if ($path->isFile() && strtolower($path->getExtension()) === 'log')
            unlink($path->getRealPath());

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
        $actions = self::getAdminActions();
        if (isset($actions[$id]) && isset($actions[$id]['route'])) {
            return $this->redirect($this->generateUrl($actions[$id]['route']));
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
