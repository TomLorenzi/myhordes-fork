<?php


namespace App\Command\Notifications;


use App\Entity\Citizen;
use App\Entity\LogEntryTemplate;
use App\Entity\TownJoinNotificationAccumulation;
use App\Entity\TownLogEntry;
use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Messages\WebPush\WebPushMessage;
use App\Service\GameFactory;
use App\Service\LogTemplateHandler;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(
    name: 'app:notifications:town-join',
    description: 'Publishes accumulated town join announcements'
)]
#[AsScheduledTask('* * * * *', description: 'Publishes accumulated town join announcements')]
class ProcessTownJoinNotificationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
        private readonly TranslatorInterface $trans,
        private readonly GameFactory $gameFactory,
        private readonly LogTemplateHandler $log,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-handle', null, InputOption::VALUE_NONE, 'Does not mark events as handled.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var TownJoinNotificationAccumulation[] $accumulators */
        $accumulators = $this->entityManager->getRepository(TownJoinNotificationAccumulation::class)
            ->createQueryBuilder('t')
            ->where('t.handled = :false')->setParameter('false', false)
            ->andWhere('t.due < :now')->setParameter('now', new \DateTime())
            ->getQuery()->execute();

        if (empty($accumulators)) return 0;

        $crow_avatar = $this->entityManager->getRepository(User::class)->find(66)?->getAvatar()?->getId();

        if (!$input->getOption('no-handle')) {
            foreach ($accumulators as $accumulator)
                $this->entityManager->persist($accumulator->setHandled(true));
            $this->entityManager->flush();
        }

        $templates = [
            $this->entityManager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_friend_enters_town']),
            $this->entityManager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_friends_enter_town']),
        ];

        foreach ($accumulators as $accumulator) {
            if (empty($accumulator->getFriends())) continue;
            if ($accumulator->getTown()->getPassword() || !$accumulator->getTown()->isOpen()) continue;
            if ($accumulator->getSubject()->getActiveCitizen()) continue;

            if (!$this->gameFactory->userCanEnterTown(
                $accumulator->getTown(),
                $accumulator->getSubject(),
                $this->entityManager->getRepository(TownSlotReservation::class)->count(['town' => $accumulator->getTown()]) > 0
            )) continue;

            $friends = array_filter(
                array_map( fn(int $id) => $this->entityManager->getRepository(User::class)->find($id), $accumulator->getFriends() ),
                fn(?User $u) => $u !== null
            );

            if (empty($friends)) continue;
            $plural = count($friends) > 1;

            /** @var TownLogEntry $entity */
            $template = $templates[$plural ? 1 : 0];
            if (!$template) continue;

            $transParams = $this->log->parseTransParams($template->getVariableTypes(), [
                'town' => $accumulator->getTown()->getName(),
                'player' => $plural ? $accumulator->getFriends() : $accumulator->getFriends()[0]
            ]);

            try {
                $text = strip_tags( $this->trans->trans($template->getText(), $transParams, 'game', $accumulator->getSubject()->getLanguage() ?? 'en') );
            } catch (\Throwable $t) {
                continue;
            }

            foreach ($accumulator->getSubject()->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription)
                $this->bus->dispatch(
                    new WebPushMessage($subscription,
                        title:         $this->trans->trans($plural ? 'Freunde sind einer Stadt beigetreten' : 'Ein Freund ist einer Stadt beigetreten', [], 'global', $accumulator->getSubject()->getLanguage() ?? 'en' ),
                        body:          $text,
                        avatar:        $crow_avatar
                    )
                );
        }

        return 0;
    }
}