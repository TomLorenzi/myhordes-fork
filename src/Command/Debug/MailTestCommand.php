<?php


namespace App\Command\Debug;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:debug:mail',
    description: 'Debug command to test the mailing service.'
)]
class MailTestCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Debug Mails.')

            ->addArgument('from', InputArgument::REQUIRED, 'Sender')
            ->addArgument('to', InputArgument::REQUIRED, 'Receiver')
            ->addArgument('title', InputArgument::OPTIONAL, 'Receiver', 'MyHordes Test Email')
            ->addArgument('text', InputArgument::OPTIONAL, 'Receiver', 'If you have received this, the email test was a success! Hurray!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->mailer->send((new Email())
                                ->from( $input->getArgument( 'from' ) )
                                ->to( $input->getArgument( 'to' ) )
                                ->subject( $input->getArgument( 'title' ) )
                                ->text( $input->getArgument( 'text' ) )
                                ->html('<p><i>' . $input->getArgument( 'text' ) . '</i></p>'));
        $output->writeln('Mail sent.');
        return 0;
    }
}
