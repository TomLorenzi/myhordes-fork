<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
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
     * @Route("jx/admin/fs/index", name="admin_file_system_dash")
     * @return Response
     */
    public function fs_index(): Response
    {
        return $this->render('ajax/admin/files/list.html.twig', $this->addDefaultTwigArgs(null, [
            'logs' => $this->adminHandler->getLogFiles(),
            'backups' => $this->adminHandler->getDbDumps()
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