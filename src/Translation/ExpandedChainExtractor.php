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
    private ?string $bundle_base = null;

    public function __construct(
        #[AutowireDecorated]
        private ChainExtractor $inner,
        KernelInterface $kernel,
    ) {
        $this->bundle_base = $kernel->getBundle('MyHordesPrimeBundle')?->getPath() ?? null;
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
        # There is no way to pass additional bundle directories to the base translation command
        # Thus, we check for the one directory we can pass, and then manually execute the extraction for the others
        if ($this->bundle_base && $directory === "{$this->bundle_base}/Resources/views") {
            $this->inner->extract("{$this->bundle_base}/Controller", $catalogue);
            $this->inner->extract("{$this->bundle_base}/Hooks", $catalogue);
            $this->inner->extract("{$this->bundle_base}/templates", $catalogue);
        }
    }
}
