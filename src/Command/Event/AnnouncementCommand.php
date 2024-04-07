<?php


namespace App\Command\Event;

use App\Entity\Announcement;
use App\Entity\AutomaticEventForecast;
use App\Entity\User;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Structures\MyHordesConf;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(
    name: 'app:event:announce',
    description: 'Announces upcoming events to the players via PM'
)]
#[AsScheduledTask('16 0 * * *', description: 'Announces upcoming events to the players via PM')]
class AnnouncementCommand extends Command
{

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly ConfMaster $conf,
        protected readonly KernelInterface $kernel,
        protected readonly Environment $twig,
        protected readonly EventProxyService $proxyService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new DateTimeImmutable();
        $cutoff = new DateTimeImmutable('today+14days');

        /** @var ArrayCollection<AutomaticEventForecast> $all_events */
        $all_events = $this->em->getRepository(AutomaticEventForecast::class)->matching(
            (new Criteria())
                ->where( Criteria::expr()->gte( 'start', $now ) )
                ->andWhere( Criteria::expr()->lte( 'start', $cutoff ) )
                ->andWhere( Criteria::expr()->eq( 'announced', false ) )
                ->orderBy(['start' => Criteria::ASC])
        )->filter(fn(AutomaticEventForecast $f) => $this->conf->eventIsPublic( $f->getEvent() ));

        if ($all_events->isEmpty()) return 0;

        $the_crow = $this->em->getRepository(User::class)->find(66);
        if (!$the_crow) return 0;

        $global_conf = $this->conf->getGlobalConf();

        $announcements = [];

        foreach ($all_events as $entry) {

            $lang_fallback_path = ['en', 'fr', 'de', 'es'];
            $lang_mapping = [];
            $langs = array_map(fn(array $item) => $item['code'], array_filter($global_conf->get(MyHordesConf::CONF_LANGS), fn(array $item) => $item['generate']));
            foreach ($langs as $lang)
                if (file_exists("{$this->kernel->getProjectDir()}/templates/event/{$entry->getEvent()}/$lang.html.twig"))
                    $lang_mapping[$lang] = $lang;
                else foreach ($lang_fallback_path as $fallback)
                    if (file_exists("{$this->kernel->getProjectDir()}/templates/event/{$entry->getEvent()}/$fallback.html.twig")) {
                        $lang_mapping[$lang] = $fallback;
                        break;
                    }

            $vars = [
                'year' => $entry->getStart()->format('Y'),
                'date_begin' => $entry->getStart(),
                'date_end' => $entry->getEnd()
            ];

            foreach ($lang_mapping as $lang => $mapping) {
                $template = $this->twig->load("event/{$entry->getEvent()}/$mapping.html.twig");
                $announcements[] = $announcement = (new Announcement())
                    ->setTitle(strip_tags($template->renderBlock('title', $vars)))
                    ->setText($template->renderBlock('content', $vars))
                    ->setTimestamp(new DateTime())
                    ->setLang($lang)
                    ->setSender($the_crow)
                    ->setValidated(true);

                $this->em->persist($announcement);
            }

            if (!empty($lang_mapping)) $this->em->persist($entry->setAnnounced(true));
            $this->em->flush();
        }

        foreach ($announcements as $announcement)
            $this->proxyService->newAnnounceEvent( $announcement );

        $this->em->flush();

        return 0;
    }
}