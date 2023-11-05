<?php

namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use App\Service\Translation\TranslationService;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;
use Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Translation\TranslatorInterface;


#[AsDecorator(decorates: 'translation.extractor.visitor.trans_method')]
final class DecoratedTransMethodVisitor extends AbstractVisitor implements NodeVisitor
{
    private string $relative_path = '';
    private string $base_path = '';
    /**
     * @var mixed|null
     */
    private ?MessageCatalogue $catalogue;

    public function __construct(
        #[AutowireDecorated]
        private readonly TransMethodVisitor $inner,
        private readonly TranslationConfigGlobal $config,
        TranslationService $trans,
        KernelInterface $appKernel
    ) {
        $this->catalogue = $config->skipExistingMessages() ? $trans->getMessageSubCatalogue(bundle: false, locale: 'de') : null;
        $this->base_path = (new \SplFileInfo($appKernel->getProjectDir()))->getRealPath();
    }

    public function initialize(MessageCatalogue $catalogue, \SplFileInfo $file, string $messagePrefix): void
    {
        $this->relative_path = $file->getRealPath();
        if ( str_starts_with( $this->relative_path, $this->base_path ) )
            $this->relative_path = substr( $this->relative_path, strlen( $this->base_path ) );
        $this->inner->initialize($catalogue,$file,$messagePrefix);
    }

    public function beforeTraverse(array $nodes): ?Node
    {
        return $this->inner->beforeTraverse( $nodes );
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$this->config->checkPath( $this->relative_path )) return null;
        return $this->inner->enterNode( $node );
    }

    public function leaveNode(Node $node): ?Node
    {
        return $this->inner->leaveNode( $node );
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return $this->inner->beforeTraverse( $nodes );
    }
}
