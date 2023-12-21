<?php

namespace App\Command;

use App\Service\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:language'
)]
class LanguageCommand extends Command
{
    protected ?string $locale = null;

    protected ?TranslatorInterface $translator = null;
    protected ?CommandHelper $helper = null;


    protected function configure(): void
    {
        $this->addOption('lang', null, InputOption::VALUE_REQUIRED, 'Select output language', 'en');
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setCommandHelper(CommandHelper $helper): void
    {
        $this->helper = $helper;
    }

    protected function translate( string $text, string $domain, array $replace = [] ): string {
        return $this->translator->trans( $text, $replace, $domain, $this->locale );
    }


    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->helper->setLanguage( $this->locale = $input->getOption('lang') );
    }

}