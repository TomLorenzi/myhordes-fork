<?php


namespace App\Service;

use App\Entity\AdminBan;
use App\Entity\AdminDeletion;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\Forum;
use App\Entity\Picto;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Service\DeathHandler;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminActionHandler
{
    private $entity_manager;
    /**
     * @var DeathHandler
     */
    private $death_handler;
    private $translator;
    private $log;
    private $userHandler;
    private $crow;

    private $requiredRole = [
        'headshot' => 'ROLE_ADMIN',
        'suicid' => 'ROLE_CROW',
        'confirmDeath' => 'ROLE_ADMIN',
        'setDefaultRoleDev' => 'ROLE_ADMIN',
        'liftAllBans' => 'ROLE_CROW',
        'ban' => 'ROLE_CROW',
        'clearReports' => 'ROLE_CROW',
        'eatLiver' => 'ROLE_CROW'
    ];

    public function __construct( EntityManagerInterface $em, DeathHandler $dh, TranslatorInterface $ti, LogTemplateHandler $lt, UserHandler $uh, CrowService $crow)
    {
        $this->entity_manager = $em;
        $this->death_handler = $dh;
        $this->translator = $ti;
        $this->log = $lt;
        $this->userHandler = $uh;
        $this->crow = $crow;
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

    private function kill_citizen($targetCitizenId, $causeOfDeath): string {
        /** @var Citizen $citizen */
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find($targetCitizenId);
        if ($citizen && $citizen->getAlive()) {
            $rem = [];
            $this->death_handler->kill( $citizen, $causeOfDeath, $rem );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $this->entity_manager->flush();
            if ($causeOfDeath == CauseOfDeath::Headshot)
                $message = $this->translator->trans('{username} wurde standrechtlich erschossen.', ['{username}' => '<span>' . $citizen->getName() . '</span>'], 'game');
            else if ($causeOfDeath === CauseOfDeath::LiverEaten)
                $message = $this->translator->trans('{username} hat keine Leber mehr.', ['{username}' => '<span>' . $citizen->getName() . '</span>'], 'game');
        }
        else {
            $message = $this->translator->trans('Dieser Bürger gehört keiner Stadt an.', [], 'game');
        }
        return $message;
    }

    public function confirmDeath(int $sourceUser, int $targetUserId): bool {
        if(!$this->hasRights($sourceUser, 'confirmDeath'))
            return false;

        /** @var User $targetUser */
        $targetUser = $this->entity_manager->getRepository(User::class)->find($targetUserId);

        $activeCitizen = $targetUser->getActiveCitizen();
        if (!(isset($activeCitizen)))
            return false;
        if ($activeCitizen->getAlive())
            return false;

        $activeCitizen->setActive(false);

        // Delete not validated picto from DB
        // Here, every validated picto should have persisted to 2
        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUserAndTown($targetUser, $activeCitizen->getTown());
        foreach ($pendingPictosOfUser as $pendingPicto) {
            $this->entity_manager->remove($pendingPicto);
        }

        CitizenRankingProxy::fromCitizen( $activeCitizen, true );

        $this->entity_manager->persist( $activeCitizen );
        $this->entity_manager->flush();

        return true;
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
}