<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\User;
use App\Service\DeathHandler;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct( EntityManagerInterface $em, DeathHandler $dh, TranslatorInterface $ti, LogTemplateHandler $lt)
    {
        $this->entity_manager = $em;
        $this->death_handler = $dh;
        $this->translator = $ti;
        $this->log = $lt;
    }

    protected function hasRights(int $sourceUser)
    {
        if ($this->entity_manager->getRepository(User::class)->find($sourceUser)->getIsAdmin()){
            return true;
        }
        return false;
    }

    public function headshot(int $sourceUser, int $targetUserId): string
    {
        if(!$this->hasRights($sourceUser)){
            return $this->translator->trans('Dazu hast Du nicht das Recht.', [], 'game');
        }
        $user = $this->entity_manager->getRepository(User::class)->find($targetUserId);
        /**
        * @var Citizen
        */
        $citizen = $user->getAliveCitizen();
        file_put_contents("../var/log/death_log.log", $citizen, FILE_APPEND);
        if (isset($citizen)) {
            $rem = [];
            $this->death_handler->kill( $citizen, CauseOfDeath::Headshot, $rem );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $this->entity_manager->flush();
            $message = $this->translator->trans('%username% wurde standrechtlich erschossen.', ['%username%' => '<span>' . $user->getUsername() . '</span>'], 'game');
        }
        else {
            $message = $this->translator->trans('%username% gehört keiner Stadt an.', ['%username%' => '<span>' . $user->getUsername() . '</span>'], 'game');
        }
        return $message;
    }
}