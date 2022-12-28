<?php

namespace App\Translation;

use MyHordes\Plugins\Fixtures\AwardTitle;
use MyHordes\Plugins\Interfaces\FixtureChainInterface;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;
use MyHordes\Plugins\Management\FixtureSourceLookup;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

final class FixtureVisitor extends AbstractVisitor implements NodeVisitor
{
    public function __construct(
        private readonly FixtureSourceLookup $lookup,
        private readonly ContainerInterface $container
    ) {}

    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    protected function extractColumnData( array $data, array|string $columns, string $domain ): bool {
        if (!is_array($columns)) $columns = [$columns];
        foreach ($columns as $column)
            foreach ( array_filter( array_column( $data, $column ) ) as $message )
                $this->addMessageToCatalogue($message, $domain, 0);
        var_dump($data);
        return true;
    }

    protected function extractData( FixtureChainInterface $provider, array $data ): bool {
        return match ($provider::class) {
            AwardTitle::class => $this->extractColumnData($data, 'title', 'game'),
            default => true,
        };
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return null;
        }

        if (is_a( (string)$node->namespacedName, FixtureProcessorInterface::class, true )) {

            $chain = $this->lookup->findChainClassByProvider( (string)$node->namespacedName );
            if (!$chain) return null;

            try {
                /** @var FixtureChainInterface $provider */
                $provider = $this->container->get( $chain );

                $this->extractData( $provider, $provider->data( (string)$node->namespacedName ) );
            } catch (\Throwable $t) { return null; }
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
