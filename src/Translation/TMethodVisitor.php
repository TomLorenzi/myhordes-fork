<?php

namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TMethodVisitor extends AbstractVisitor implements NodeVisitor
{
    private ?MessageCatalogue $catalogue = null;

    private string $base_path = '';
    private string $relative_path = '';
    private TranslationConfigGlobal $config;

    public function __construct(TranslationConfigGlobal $config, TranslatorInterface $trans, KernelInterface $appKernel)
    {
        $this->config = $config;
        $this->catalogue = $config->skipExistingMessages() ? $trans->getCatalogue('de') : null;
        $this->base_path = (new \SplFileInfo($appKernel->getProjectDir()))->getRealPath();
    }

    public function initialize(MessageCatalogue $catalogue, \SplFileInfo $file, string $messagePrefix): void
    {
        $this->relative_path = $file->getRealPath();
        if ( str_starts_with( $this->relative_path, $this->base_path ) )
            $this->relative_path = substr( $this->relative_path, strlen( $this->base_path ) );
        parent::initialize($catalogue,$file,$messagePrefix);
    }

    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    protected function resolveStringlyArgument(Node\Expr\CallLike $node, int|string $index): array {
        // Use default getter to see if the argument maybe is just a bunch of strings
        $strings = $this->getStringArguments($node, $index);
        if ($strings) return $strings;

        // Fetch the argument to see what it is
        $args = !$node->isFirstClassCallable() ? $node->getArgs() : $node->getRawArgs();

        if (is_int($index)) $arg = $args[$index] ?? null;
        else $arg = array_values(array_filter( $args, fn(Node\Arg $a) => $a->name instanceof Node\Identifier && (string)$a->name == $index ))[0] ?? null;
        if (!$arg || !isset($arg->value) || !$arg->value) return [];

        $value = $arg->value;

        // List of expressions that will evaluate to a string at some point
        $candidates = [];

        // If the value is a ternary, the if and else branch are likely strings
        if ($value instanceof Node\Expr\Ternary)
            $candidates = [$value->if, $value->else];

        // We now have a list of candidates; let's try converting them to strings!
        return array_filter( array_map( function(Node\Expr $expr) {

            if ($expr instanceof Node\Scalar\String_) return $expr->value;

            return null;

        }, $candidates ), fn(?string $s) => $s !== null );
    }

    protected function addMessageToCatalogue(string $message, ?string $domain, int $line): void {
        if ($this->catalogue?->has($message,$domain) || !$this->config->checkPath( $this->relative_path )) return;
		$this->config->add_source_for($message, $domain, "php", $this->relative_path);
        parent::addMessageToCatalogue($message,$domain,$line);
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Expr\StaticCall && !$node instanceof Node\Expr\MethodCall && !$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if (!\is_string($node->name) && !$node->name instanceof Node\Identifier && !$node->name instanceof Node\Name) {
            return null;
        }

        if ($node instanceof Node\Expr\StaticCall && !\is_string($node->class) && !$node->class instanceof Node\Identifier && !$node->class instanceof Node\Name) {
            return null;
        }

        $name = (string)$node->name;
        $class = $node instanceof Node\Expr\StaticCall ? (string)$node->class : T::class;

        $argName = $class === T::class ? match ($name) {
            '__' => 's',
            'trans', 't' => 'message',
            default => null
        } : null;

        $argIndex = match ($name) {
            '__' => 1,
            'trans', 't' => 2,
            default => null
        };

        if ($argName) {
            $nodeHasNamedArguments = $this->hasNodeNamedArguments($node);

            if (!$messages = $this->resolveStringlyArgument($node, $nodeHasNamedArguments ? $argName : 0))
                return null;

            $domain = $this->getStringArguments($node, $nodeHasNamedArguments ? 'domain' : $argIndex)[0] ?? null;

            if ($domain)
                foreach ($messages as $message)
                    if (!empty($message))
                        $this->addMessageToCatalogue($message, $domain, $node->getStartLine());
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
