<?php

namespace App\Translation;

use Symfony\Component\Translation\MessageCatalogue;

/**
 * This class only exists as a proxy to run the NameResolver visitor as part of the translation node traversal.
 */
final class NameResolverVisitor extends \PhpParser\NodeVisitor\NameResolver
{

    public function initialize(MessageCatalogue $catalogue, \SplFileInfo $file, string $messagePrefix): void
    {}

}
