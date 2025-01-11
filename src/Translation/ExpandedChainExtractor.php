<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Translation;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

#[AsDecorator(decorates: 'translation.extractor')]
class ExpandedChainExtractor implements ExtractorInterface
{
    public function __construct(
        #[AutowireDecorated]
        private ChainExtractor $inner,
        KernelInterface $kernel,
    ) {

    }

    public function addExtractor(string $format, ExtractorInterface $extractor): void
    {
        $this->inner->addExtractor( $format, $extractor );
    }

    public function setPrefix(string $prefix): void
    {
        $this->inner->setPrefix( $prefix );
    }

    /**
     * @return void
     */
    public function extract(string|iterable $directory, MessageCatalogue $catalogue)
    {
        $this->inner->extract($directory, $catalogue);
    }
}
