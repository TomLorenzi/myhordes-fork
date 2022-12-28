<?php

namespace App\Command\Translation;

use Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

#[AsCommand(name: 'app:translation:bundle', description: 'Extract missing translations keys from code to translation files in a bundle.')]
class TranslationBundleUpdateCommand extends TranslationUpdateCommand
{

    public function __construct(TranslationWriterInterface $writer, TranslationReaderInterface $reader, ExtractorInterface $extractor)
    {
        parent::__construct($writer, $reader, $extractor, 'de', null, null, [], [], ['de','en','fr','es']);
    }
}

