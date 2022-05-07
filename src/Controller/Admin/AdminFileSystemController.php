<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractController;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use DirectoryIterator;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
 */
class AdminFileSystemController extends AdminActionController
{

    /**
     * @param string $base_path
     * @param string|string[] $extensions
     * @return SplFileInfo[]
     */
    protected function list_files( string $base_path, $extensions ): array {
        if (!is_array($extensions)) $extensions = [$extensions];

        $result = [];

        $paths = [$base_path];
        while (!empty($paths)) {
            $path = array_pop($paths);
            if (!is_dir($path)) continue;
            foreach (new DirectoryIterator($path) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isDot() || $fileInfo->isLink()) continue;
                elseif ($fileInfo->isFile() && in_array( strtolower($fileInfo->getExtension()), $extensions))
                    $result[] = $fileInfo->getFileInfo( SplFileInfo::class );
                elseif ($fileInfo->isDir()) $paths[] = $fileInfo->getRealPath();
            }
        }

        return $result;
    }

    /**
     * @Route("jx/admin/fs/index", name="admin_file_system_dash")
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $trans
     * @return Response
     */
    public function fs_index(ParameterBagInterface $params, TranslatorInterface $trans): Response
    {
        $log_base_dir = "{$params->get('kernel.project_dir')}/var/log";
        $log_spl_base_path = new SplFileInfo($log_base_dir);
        $extract_log_type = function(SplFileInfo $f) use ($log_spl_base_path, $trans): array {
            if ($f->getPathInfo(SplFileInfo::class)->getRealPath() === $log_spl_base_path->getRealPath()) return [
                'tag' => $trans->trans('Kernel', [], 'admin'),
                'color' => '#F71735'
            ];
            else switch ($f->getPathInfo(SplFileInfo::class)->getFilename()) {
                case 'night': return [
                    'tag' => $trans->trans('Angriff', [], 'admin'),
                    'color' => '#1481BA'
                ];
                case 'update': return [
                    'tag' => $trans->trans('Update', [], 'admin'),
                    'color' => '#82846D'
                ];
                case 'admin': return [
                        'tag' => $trans->trans('Admin', [], 'admin'),
                        'color' => '#ff6633'
                ];
                default: return [
                    'tag' => $trans->trans('Unbekannt', [], 'admin'),
                    'color' => '#646165'
                ];

            }
        };

        $log_files = array_map( fn($e) => [
            'info' => $e,
            'rel' => $e->getRealPath(),
            'time' => (new \DateTime())->setTimestamp( $e->getMTime() ),
            'access' => str_replace(['/','\\'],'::', $e->getRealPath()),
            'tags' => [$extract_log_type($e)]
        ], $this->list_files( $log_base_dir, 'log' ));
        usort($log_files, fn($a,$b) => $b['time'] <=> $a['time'] );

        $backup_base_dir = "{$params->get('kernel.project_dir')}/var/backup";
        $extract_backup_types = function(SplFileInfo $f) use ($trans): array {
            $ret = [];

            list($type) = explode( '.', array_pad( explode('_', $f->getFilename() ), 3, '')[2], 2);
            switch ($type) {
                case 'nightly': $ret[] = [ 'color' => '#0D090A', 'tag' => $trans->trans('Angriff', [], 'admin')]; break;
                case 'daily':   $ret[] = [ 'color' => '#361F27', 'tag' => $trans->trans('Täglich', [], 'admin')]; break;
                case 'weekly':  $ret[] = [ 'color' => '#521945', 'tag' => $trans->trans('Wöchentlich', [], 'admin')]; break;
                case 'monthly': $ret[] = [ 'color' => '#912F56', 'tag' => $trans->trans('Monatlich', [], 'admin')]; break;
                case 'update':  $ret[] = [ 'color' => '#738290', 'tag' => $trans->trans('Update', [], 'admin')]; break;
                case 'manual':  $ret[] = [ 'color' => '#CD533B', 'tag' => $trans->trans('Manuell', [], 'admin')]; break;
            }

            switch ($f->getExtension()) {
                case 'sql': $ret[] = [ 'color' => '#3E5641', 'tag' => $trans->trans('Nicht komprimiert', [], 'admin')]; break;
                case 'xz':  $ret[] = [ 'color' => '#40916C', 'tag' => 'XZ']; break;
                case 'gzip':$ret[] = [ 'color' => '#2D6A4F', 'tag' => 'GZIP']; break;
                case 'bz2': $ret[] = [ 'color' => '#1B4332', 'tag' => 'BZIP2']; break;
            }

            return $ret;
        };

        $backup_files = array_map( fn($e) => [
            'info' => $e,
            'rel' => str_replace($params->get('kernel.project_dir'),'', $e->getRealPath()),
            'time' => (new \DateTime())->setTimestamp( $e->getCTime() ),
            'access' => str_replace(['/','\\'],'::', str_replace("$backup_base_dir/",'', $e->getRealPath())),
            'tags' => $extract_backup_types($e)
        ], $this->list_files( $backup_base_dir, ['sql','xz','gzip','bz2'] ));
        usort($backup_files, fn($a,$b) => $b['time'] <=> $a['time'] );

        return $this->render('ajax/admin/files/list.html.twig', $this->addDefaultTwigArgs(null, [
            'logs' => $log_files,
            'backups' => $backup_files
        ]));
    }

    /**
     * @Route("admin/fs/log/fetch/{a}/{f}", name="admin_log", condition="!request.isXmlHttpRequest()")
     * @AdminLogProfile(enabled=true)
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

        $spl_core_path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/log");
        $path = new SplFileInfo($f);
        if (!$path->isFile() || strtolower($path->getExtension()) !== 'log' || !str_starts_with( $path->getRealPath(), $spl_core_path->getRealPath() )) return new Response('', 404);

        if ($path->getSize() > 67108864)        $a = 'download';
        else if ($path->getSize() > 16777216 && $a === 'view') $a = 'print';

        switch ($a) {
            case 'view': return $this->render( 'web/logviewer.html.twig', [
                'filename' => $path->getRealPath(),
                'log' => file_get_contents($path->getRealPath()),
            ] );
            case 'print': return $this->file($path->getRealPath(), $path->getFilename(), ResponseHeaderBag::DISPOSITION_INLINE);
            case 'download': return $this->file($path->getRealPath(), $path->getFilename(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            default: return new Response('', 403);
        }
    }

    /**
     * @Route("admin/fs/townlog/fetch/{id<\d+>}/{type}.txt", name="admin_townlog", condition="!request.isXmlHttpRequest()")
     * @AdminLogProfile(enabled=true)
     * @param int $id
     * @param string $type
     * @return Response
     */
    public function townlog(int $id, string $type): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return new Response('', 403);

        if (!in_array($type, ['register', 'zones', 'all', 'citizens'])) return new Response('', 404);

        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return new Response('', 404);

        $q = $this->entity_manager->getRepository(TownLogEntry::class)->createQueryBuilder('t')
            ->andWhere('t.town = :town' )->setParameter( 'town', $town );

        if ($type === 'register') $q->andWhere('t.zone IS NULL');
        if ($type === 'zones') $q->orderBy( 't.zone', 'ASC' );
        if ($type === 'citizens') $q->orderBy( 't.citizen', 'ASC' );
        $q->addOrderBy( 't.timestamp', 'DESC' );

        $iterator = $q->getQuery()->toIterable();

        $response = new StreamedResponse();
        $response->headers->set('Content-Disposition',
                                $response->headers->makeDisposition(
                                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                                    "$type.txt"
                                )
        );

        $response->setCallback( function() use (&$iterator, $type) {
            $last_obj = false;

            $batch = 100; $i = 0;
            foreach ($iterator as $entity) {
                $i++;

                /** @var TownLogEntry $entity */
                $template = $entity->getLogEntryTemplate();
                if (!$template) continue;

                $entityVariables = $entity->getVariables();
                $data = [
                    'timestamp' => $entity->getTimestamp(),
                    'hidden'    => $entity->getHidden(),
                    'citizen'   => $entity->getCitizen(),
                    'zone'      => $entity->getZone()
                ];

                $variableTypes = $template->getVariableTypes();
                $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);

                try {
                    $data['text'] = strip_tags( $this->translator->trans($template->getText(), $transParams, 'game') );
                }
                catch (\Throwable $t) {
                    $data['text'] = "null";
                }

                if ($type === 'zones' && $last_obj !== $entity->getZone()) {
                    if ($last_obj !== false) echo "\r\n\r\n";
                    if ($entity->getZone() === null) echo "=== TOWN REGISTER ===\r\n";
                    else echo "=== REGISTER ZONE [ {$entity->getZone()->getX()} / {$entity->getZone()->getY()} ] ===\r\n";
                    $last_obj = $entity->getZone();
                }

                if ($type === 'citizens' && $last_obj !== $entity->getCitizen()) {
                    if ($last_obj !== false) echo "\r\n\r\n";
                    if ($entity->getCitizen() === null) echo "=== REGISTER NON-ASSOCIATED ===\r\n";
                    else echo "=== REGISTER CITIZEN [ {$entity->getCitizen()->getId()} | {$entity->getCitizen()->getName()} ] ===\r\n";
                    $last_obj = $entity->getCitizen();
                }

                echo '[' . date_format( $data['timestamp'], 'd-m-y H:i') . '] ' .
                    ( !in_array( $type, ['register', 'zones'] ) ? ('[ ' . ($data['zone'] ? "{$data['zone']->getX()} / {$data['zone']->getY()}" : 'TOWN') . ' ] ') : '' ) .
                    ( ($type !== 'citizens' && $data['citizen']) ? "[ {$data['citizen']->getId()} | {$data['citizen']->getName()} ] " : '' ) .
                    ( $data['hidden'] ? "[ HIDDEN ] " : '' ) .
                    "{$data['text']}\r\n";

                if (($i % $batch) === 0) {
                    flush();
                    $this->entity_manager->clear();
                }
            }

            flush();
        } );

        return $response;
    }

    /**
     * @Route("admin/fs/townlog/fetch/{f}", name="admin_backup", condition="!request.isXmlHttpRequest()")
     * @AdminLogProfile(enabled=true)
     * @param ParameterBagInterface $params
     * @param string $f
     * @return Response
     */
    public function backup(ParameterBagInterface $params, string $f = ''): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return new Response('', 403);

        if (empty($f)) $f = $params->get('kernel.environment');
        $f = str_replace(['..','::'],['','/'],$f);

        $spl_core_path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/backup");
        $path = new SplFileInfo($f);
        if (!$path->isFile() || !in_array(strtolower($path->getExtension()), ['sql','xz','gzip','bz2']) || !str_starts_with( $path->getRealPath(), $spl_core_path->getRealPath() )) return new Response('', 404);

        $this->logger->invoke("Admin <info>{$this->getUser()->getName()}</info> downloaded backup <debug>{$f}</debug>");

        return $this->file($path->getRealPath(), $path->getFilename(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    /**
     * @Route("api/admin/fs/log/delete/{f}", name="api_admin_clear_log")
     * @AdminLogProfile(enabled=true)
     * @param ParameterBagInterface $params
     * @param string $f
     * @return Response
     */
    public function clear_log_api(ParameterBagInterface $params, string $f = ''): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (empty($f)) $f = $params->get('kernel.environment');
        $f = str_replace(['..','::'],['','/'],$f);

        $spl_core_path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/log");
        $path = new SplFileInfo($f);
        if ($path->isFile() && strtolower($path->getExtension()) !== 'log' && str_starts_with( $path->getRealPath(), $spl_core_path->getRealPath() ))
            unlink($path->getRealPath());

        $this->logger->invoke("Admin <info>{$this->getUser()->getName()}</info> deleted log <debug>{$f}</debug>");

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/fs/backup/delete/{f}", name="api_admin_clear_backup")
     * @AdminLogProfile(enabled=true)
     * @param ParameterBagInterface $params
     * @param string $f
     * @return Response
     */
    public function clear_backup_api(ParameterBagInterface $params, string $f = ''): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (empty($f)) $f = $params->get('kernel.environment');
        $f = str_replace(['..','::'],['','/'],$f);

        $spl_core_path = new SplFileInfo("{$params->get('kernel.project_dir')}/var/backup");
        $path = new SplFileInfo($f);
        if ($path->isFile() && in_array(strtolower($path->getExtension()), ['sql','xz','gzip','bz2']) && str_starts_with( $path->getRealPath(), $spl_core_path->getRealPath() ))
            unlink($path->getRealPath());

        $this->logger->invoke("Admin <info>{$this->getUser()->getName()}</info> deleted backup <debug>{$f}</debug>");

        return AjaxResponse::success();
    }

}