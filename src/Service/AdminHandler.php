<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\Post;
use App\Entity\User;
use App\Structures\MyHordesConf;
use DateTime;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpParser\Node\Param;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminHandler
{
    private EntityManagerInterface $entity_manager;
    /**
     * @var DeathHandler
     */
    private DeathHandler $death_handler;
    private TranslatorInterface $translator;
    private LogTemplateHandler $log;
    private UserHandler $userHandler;
    private CrowService $crow;
    private ParameterBagInterface $params;
    private MyHordesConf $conf;

    private $requiredRole = [
        'headshot' => 'ROLE_SUB_ADMIN',
        'suicid' => 'ROLE_CROW',
        'setDefaultRoleDev' => 'ROLE_SUB_ADMIN',
        'liftAllBans' => 'ROLE_CROW',
        'ban' => 'ROLE_CROW',
        'clearReports' => 'ROLE_CROW',
        'eatLiver' => 'ROLE_CROW'
    ];

    public function __construct( EntityManagerInterface $em, DeathHandler $dh, TranslatorInterface $ti, LogTemplateHandler $lt, UserHandler $uh, CrowService $crow, ParameterBagInterface $params, ConfMaster $conf)
    {
        $this->entity_manager = $em;
        $this->death_handler = $dh;
        $this->translator = $ti;
        $this->log = $lt;
        $this->userHandler = $uh;
        $this->crow = $crow;
        $this->params = $params;
        $this->conf = $conf->getGlobalConf();
    }

    protected function hasRights(int $sourceUser, string $desiredAction)
    {
        if (!isset($this->requiredRole[$desiredAction])) return false;
        $acting_user = $this->entity_manager->getRepository(User::class)->find($sourceUser);
        return $acting_user && $this->userHandler->hasRole( $acting_user, $this->requiredRole[$desiredAction] );
    }

    public function headshot(int $sourceUser, int $targetCitizenId): string
    {
        if(!$this->hasRights($sourceUser, 'headshot'))
            return $this->translator->trans('Dazu hast Du kein Recht.', [], 'game');

        return $this->kill_citizen($targetCitizenId, CauseOfDeath::Headshot);
    }

    public function eatLiver(int $sourceUser, int $targetCitizenId): string
    {
        if(!$this->hasRights($sourceUser, 'eatLiver'))
            return $this->translator->trans('Dazu hast Du kein Recht.', [], 'game');

        return $this->kill_citizen($targetCitizenId, CauseOfDeath::LiverEaten);
    }

    private function kill_citizen(int $targetCitizenId, int $causeOfDeath): string {
        /** @var Citizen $citizen */
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find($targetCitizenId);
        if ($citizen !== null && $citizen->getAlive()) {
            $rem = [];
            $this->death_handler->kill( $citizen, $causeOfDeath, $rem );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $this->entity_manager->flush();
			$cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => $causeOfDeath]);
            if ($causeOfDeath === CauseOfDeath::Headshot)
                $message = $this->translator->trans('{username} wurde standrechtlich erschossen.', ['{username}' => '<span>' . $citizen->getName() . '</span>'], 'game');
            else if ($causeOfDeath === CauseOfDeath::LiverEaten)
                $message = $this->translator->trans('{username} hat keine Leber mehr.', ['{username}' => '<span>' . $citizen->getName() . '</span>'], 'game');
			else
				$message = $this->translator->trans('Verrat! {citizen} ist gestorben: {cod}.', ['{username}' => '<span>' . $citizen->getName() . '</span>', '{cod}' => $this->translator->trans($cod->getLabel(), [], "game")], 'game');
        }
        else {
            $message = $this->translator->trans('Dieser Bürger gehört keiner Stadt an.', [], 'game');
        }
        return $message;
    }

    public function clearReports(int $sourceUser, int $postId): bool {

        if (!$this->hasRights($sourceUser, 'clearReports'))
            return false;

        $post = $this->entity_manager->getRepository(Post::class)->find($postId);
        $reports = $post->getAdminReports();
        
        try 
        {
            foreach ($reports as $report) {
                $report->setSeen(true);
                $this->entity_manager->persist($report);
            }
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function setDefaultRoleDev(int $sourceUser, bool $asDev): bool {

        if (!$this->hasRights($sourceUser, 'setDefaultRoleDev'))
            return false;

        $user = $this->entity_manager->getRepository(User::class)->find($sourceUser);
            
        if ($asDev) {
            $defaultRole = "DEV";
        }    
        else {
            $defaultRole = "USER";
        }
        
        try 
        {
            $user->setPostAsDefault($defaultRole);
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function suicid(int $sourceUser): string
    {
        if(!$this->hasRights($sourceUser, 'suicid'))
            return $this->translator->trans('Dazu hast Du kein Recht.', [], 'game');   

        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($sourceUser);
        $citizen = $user->getActiveCitizen();
        if ($citizen !== null && $citizen->getAlive()) {
            $rem = [];
            $this->death_handler->kill( $citizen, CauseOfDeath::Strangulation, $rem );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $this->entity_manager->flush();
            $message = $this->translator->trans('Du hast dich umgebracht.', [], 'admin');
        }
        else {
            $message = $this->translator->trans('Du gehörst keiner Stadt an.', [], 'admin');
        }
        return $message;
    }

    /**
     * @param string $base_path
     * @param string|string[] $extensions
     * @return SplFileInfo[]
     */
    public function list_local_files(string $base_path, array|string $extensions ): array {
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
     * @param \FTP\Connection $ftp_conn
     * @param string $base_path
     * @param string|string[] $extensions
     * @return array[]
     */
    public function list_ftp_files(\FTP\Connection $ftp_conn, string $base_path, array|string $extensions ): array {
        if (!is_array($extensions)) $extensions = [$extensions];

        $result = [];

        $paths = [$base_path];
        while (!empty($paths)) {
            $path = array_pop($paths);

            $ftpFiles = ftp_mlsd($ftp_conn, $base_path);

            foreach ($ftpFiles as $ftpFile){
                if (in_array($ftpFile['type'], ['cdir', 'pdir', 'dir'])) continue;
                if (!str_contains($ftpFile['name'], '.')) continue; // No dot, then no extension

                $details = explode('.', $ftpFile['name']);
                $ext = $details[count($details) - 1];
                if (in_array( strtolower($ext), $extensions)) {
                    $result[] = $ftpFile;
                }
            }
        }

        return $result;
    }

    /**
     * @param $conn
     * @param string $base_path
     * @param string|string[] $extensions
     * @return array[]
     */
    public function list_sftp_files($conn, string $base_path, array|string $extensions ): array {
        if (!is_array($extensions)) $extensions = [$extensions];

        $result = [];

        $paths = [$base_path];
        while (!empty($paths)) {
            $path = array_pop($paths);

            $sftp_fd = ssh2_sftp($conn);
            $handle = opendir("ssh2.sftp://$sftp_fd$path");

            while (false != ($entry = readdir($handle))){
                if (in_array($entry, ['.', '..'])) continue;
                if (!str_contains($entry, '.')) continue; // No dot, then no extension

                $details = explode('.', $entry);
                $ext = $details[count($details) - 1];

                // Not an SQL dump, ignore it
                if (in_array( strtolower($ext), $extensions)) {
                    $stat = ssh2_sftp_stat($sftp_fd, $path . "/" . $entry);
                    $result[] = [
                        'name' => $path . '/' . $entry,
                        'size' => $stat['size'],
                        'time' => $stat['atime']
                    ];
                }
            }
            closedir($handle);
        }

        return $result;
    }

    public function getDbDumps(): array {
        $storages = $this->conf->getData()['backup']['storages'];

        if (count($storages) == 0) return [];

        $extract_backup_types = function(string $filename, string $extension, string $storage) : array {
            $ret = [];

            list($type) = explode( '.', array_pad( explode('_', $filename ), 3, '')[2], 2);

            $ret[] = match($storage) {
                'local' => ['color' => "#008080", 'tag' => 'Local'],
                'ftp' => ['color' => "#000080", 'tag' => 'FTP'],
                'sftp' => ['color' => "#800080", 'tag' => 'SFTP'],
            };

            $ret[] = match ($type) {
                'nightly' => ['color' => '#0D090A', 'tag' => $this->translator->trans('Angriff', [], 'admin')],
                'daily' => ['color' => '#361F27', 'tag' => $this->translator->trans('Täglich', [], 'admin')],
                'weekly' => ['color' => '#521945', 'tag' => $this->translator->trans('Wöchentlich', [], 'admin')],
                'monthly' => ['color' => '#912F56', 'tag' => $this->translator->trans('Monatlich', [], 'admin')],
                'update' => ['color' => '#738290', 'tag' => $this->translator->trans('Update', [], 'admin')],
                'manual' => ['color' => '#CD533B', 'tag' => $this->translator->trans('Manuell', [], 'admin')],
            };

            $ret[] = match ($extension) {
                'sql' => ['color' => '#3E5641', 'tag' => $this->translator->trans('Nicht komprimiert', [], 'admin')],
                'xz' => ['color' => '#40916C', 'tag' => 'XZ'],
                'gzip' => ['color' => '#2D6A4F', 'tag' => 'GZIP'],
                'bz2' => ['color' => '#1B4332', 'tag' => 'BZIP2'],
            };

            return $ret;
        };

        $backup_files = [];
	foreach ($storages as $name => $storage) {
            if (!$storage['enabled']) continue;
            switch($storage['type']) {
                case "local":
                    $targetPath = str_replace("~", $this->params->get('kernel.project_dir'), $storage['path']);
                    $files = $this->list_local_files( $targetPath, ['sql','xz','gzip','bz2'] );
                    $backup_files = array_merge($backup_files, array_map( fn($e) => [
                        'info' => $e,
                        'rel' => $e->getRealPath(),
                        'time' => (new \DateTime())->setTimestamp( $e->getCTime() ),
                        'access' => str_replace(['/','\\'],'::', "{$storage['type']}#{$name}#" . $e->getRealPath()),
                        'tags' => $extract_backup_types($e->getFilename(), $e->getExtension(), $storage['type'])
                    ], $files));
                    break;
                case "ftp":
                    $ftp_conn = $this->connectToFtp($storage['host'], $storage['port'], $storage['user'], $storage['pass'], $storage['passive']);
                    if (!$ftp_conn) break;
                    $files = $this->list_ftp_files($ftp_conn, $storage['path'], ['sql','xz','gzip','bz2']);
                    $backup_files = array_merge($backup_files, array_map( fn($e) => [
                        'info' => $e,
                        'rel' => "ftp://{$storage['host']}{$storage['path']}/{$e['name']}",
                        'time' => DateTime::createFromFormat("YmdHis", $e['modify']),
                        'access' => str_replace(['/','\\'], '::', "{$storage['type']}#{$name}#{$storage['path']}::{$e['name']}"),
                        'tags' => $extract_backup_types($e['name'], explode('.', $e['name'])[count(explode('.', $e['name'])) - 1], $storage['type'])
                    ], $files));
                    ftp_close($ftp_conn);
                    break;
                case "sftp":
                    $conn = $this->connectToSftp($storage['host'], $storage['port'], $storage['user'], $storage['pass']);
                    if (!$conn) break;
                    $files = $this->list_sftp_files($conn, $storage['path'], ['sql','xz','gzip','bz2']);
                    $backup_files = array_merge($backup_files, array_map( fn($e) => [
                        'info' => $e,
                        'rel' => "sftp://{$storage['host']}{$e['name']}",
                        'time' => (new \DateTime())->setTimestamp( $e['time'] ),
                        'access' => str_replace(['/','\\'], '::', "{$storage['type']}#{$name}#{$e['name']}"),
                        'tags' => $extract_backup_types($e['name'], explode('.', $e['name'])[count(explode('.', $e['name'])) - 1], $storage['type'])
                    ], $files));

                    ssh2_disconnect($conn);
                    break;
            }
        }

        usort($backup_files, fn($a,$b) => $b['time'] <=> $a['time'] );
        return $backup_files;
    }

    public function getLogFiles(): array {
        $log_base_dir = "{$this->params->get('kernel.project_dir')}/var/log";
        $log_spl_base_path = new SplFileInfo($log_base_dir);
        $extract_log_type = function(SplFileInfo $f) use ($log_spl_base_path): array {
            if ($f->getPathInfo(SplFileInfo::class)->getRealPath() === $log_spl_base_path->getRealPath()) return [
                'tag' => $this->translator->trans('Kernel', [], 'admin'),
                'color' => '#F71735'
            ];
            else return match ($f->getPathInfo(SplFileInfo::class)->getFilename()) {
                'night' => [
                    'tag' => $this->translator->trans('Angriff', [], 'admin'),
                    'color' => '#1481BA'
                ],
                'update' => [
                    'tag' => $this->translator->trans('Update', [], 'admin'),
                    'color' => '#82846D'
                ],
                'admin' => [
                    'tag' => $this->translator->trans('Admin', [], 'admin'),
                    'color' => '#ff6633'
                ],
                default => [
                    'tag' => $this->translator->trans('Unbekannt', [], 'admin'),
                    'color' => '#646165'
                ],
            };
        };

        $log_files = array_map( fn($e) => [
            'info' => $e,
            'rel' => $e->getRealPath(),
            'time' => (new \DateTime())->setTimestamp( $e->getMTime() ),
            'access' => str_replace(['/','\\'],'::', $e->getRealPath()),
            'tags' => [$extract_log_type($e)]
        ], $this->list_local_files( $log_base_dir, 'log' ));
        usort($log_files, fn($a,$b) => $b['time'] <=> $a['time'] );
        return $log_files;
    }

    public function connectToFtp($host, $port, $user, $pass, $passive): \FTP\Connection|false {
        $ftp_conn = ftp_connect($host, $port);
        if (!@ftp_login($ftp_conn, $user, $pass)) {
            ftp_close($ftp_conn);
            return false;
        }

        if ($passive)
            ftp_pasv($ftp_conn, true);

        return $ftp_conn;
    }

    public function connectToSftp($host, $port, $user, $pass)
    {
        $connection = ssh2_connect($host,$port);
        if (!$connection) return false;

        if(!ssh2_auth_password($connection, $user, $pass)) {
            ssh2_disconnect($connection);
            return false;
        }
        return $connection;
    }
}
