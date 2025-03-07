<?php

namespace App\Translation;

use Adbar\Dot;
use App\Service\Actions\Game\AtomProcessors\Effect\ProcessMessageEffect;
use App\Service\Globals\TranslationConfigGlobal;
use App\Service\Translation\TranslationService;
use ArrayHelpers\Arr;
use MyHordes\Plugins\Fixtures\Action;
use MyHordes\Plugins\Fixtures\AwardFeature;
use MyHordes\Plugins\Fixtures\AwardTitle;
use MyHordes\Plugins\Fixtures\Building;
use MyHordes\Plugins\Fixtures\CitizenComplaint;
use MyHordes\Plugins\Fixtures\CitizenDeath;
use MyHordes\Plugins\Fixtures\CitizenHomeLevel;
use MyHordes\Plugins\Fixtures\CitizenHomeUpgrade;
use MyHordes\Plugins\Fixtures\CitizenProfession;
use MyHordes\Plugins\Fixtures\CitizenRole;
use MyHordes\Plugins\Fixtures\CitizenStatus;
use MyHordes\Plugins\Fixtures\CouncilEntry;
use MyHordes\Plugins\Fixtures\ForumThreadTag;
use MyHordes\Plugins\Fixtures\GazetteEntry;
use MyHordes\Plugins\Fixtures\HeroSkill;
use MyHordes\Plugins\Fixtures\Item;
use MyHordes\Plugins\Fixtures\ItemCategory;
use MyHordes\Plugins\Fixtures\Log;
use MyHordes\Plugins\Fixtures\Picto;
use MyHordes\Plugins\Fixtures\Recipe;
use MyHordes\Plugins\Fixtures\Ruin;
use MyHordes\Plugins\Fixtures\Town;
use MyHordes\Plugins\Fixtures\ZoneTag;
use MyHordes\Plugins\Interfaces\FixtureChainInterface;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;
use MyHordes\Plugins\Management\FixtureSourceLookup;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FixtureVisitor extends AbstractVisitor implements NodeVisitor
{
    private ?MessageCatalogue $catalogue = null;
	private TranslationConfigGlobal $configGlobal;
	private string $base_path = '';
	private string $relative_path = '';

	public function __construct(
        TranslationConfigGlobal $config, TranslationService $trans,
        private readonly FixtureSourceLookup $lookup,
        private readonly ContainerInterface $container, KernelInterface $appKernel
    ) {
        $this->catalogue = $config->skipExistingMessages() ? $trans->getMessageSubCatalogue(bundle: false, locale: 'de') : null;
		$this->configGlobal = $config;
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

    protected function addMessageToCatalogue(string $message, ?string $domain, int $line): void {
        if (empty($message) || $this->catalogue?->has($message,$domain)) return;
		$this->configGlobal->add_source_for($message, $domain, 'fixture', $this->relative_path);
        parent::addMessageToCatalogue($message,$domain,$line);
    }

    protected function extractArrayData( array $data, string $domain ): bool {
        foreach ( array_filter($data) as $message )
            $this->addMessageToCatalogue($message, $domain, 0);
        return true;
    }

    protected function extractNestedArrayData( array $data, string $domain ): bool {
        $messages = [];
        array_walk_recursive( $data, function($message) use (&$messages) {
            $messages[] = $message;}
        );
        return $this->extractArrayData( $messages, $domain );
    }

    protected function extractColumnData( array $data, array|string $columns, string $domain ): bool {
        return array_reduce(
            array_map(
                function($column) use ($data, $domain) {
                    if (str_contains( $column, '.' )) {
                        [$head, $tail] = explode('.', $column, 2);
                        return $this->extractArrayData( array_map(fn( mixed $a ) => is_array( $a ) ? Arr::get( $a, $tail ) : null, array_column( $data, $head ) ), $domain );
                    } else return $this->extractArrayData( array_column( $data, $column ), $domain );
                },
                is_array($columns) ? $columns : [$columns]
            ),
            fn($c,$r) => $r && $c,
            true
        );
    }

    protected function extractActionAtomData(array $data): bool {
        // $this->addMessageToCatalogue($message, $domain, 0);
        $dot = new Dot($data);
        $flat = $dot->flatten();

        foreach ($flat as $key => $value)
            if (!is_array($value) && str_starts_with( $key, 'meta_results.' ) && str_ends_with( $key, '.processor' )) {
                $payload = implode( '.', array_slice( explode('.', $key), 0, -1 ) ) . '.payload';

                switch ($value) {
                    case ProcessMessageEffect::class: {
                        $this->addMessageToCatalogue( $dot->get( "$payload.text", '' ), $dot->get( "$payload.domain", 'items' ), 0 );
                        break;
                    }
                }
            }
        return true;
    }

    protected function extractData( FixtureChainInterface $provider, array $data ): bool {
        return match ($provider::class) {
            Action::class =>
                $this->extractActionAtomData( $data ) &&
                $this->extractArrayData( $data['message_keys'] ?? [], 'items') &&
                $this->extractColumnData( $data['meta_requirements'] ?? [], 'text', 'items') &&
                $this->extractColumnData( $data['actions'] ?? [], ['label','tooltip','confirmMsg','message','escort_message','target.note'], 'items') &&
                $this->extractColumnData( $data['escort'] ?? [], ['label','tooltip'], 'items') &&
                $this->extractColumnData( $data['heroics'] ?? [], ['used'], 'items'),
            AwardTitle::class => $this->extractColumnDataAndHandleFemaleTitles($data, 'title', 'game'),
            Item::class =>
                $this->extractColumnData( $data, ['label','description'], 'items'),
            Recipe::class => $this->extractColumnData( $data, ['action','tooltip'], 'items'),
            ItemCategory::class => $this->extractColumnData( $data, 'label', 'items'),
            AwardFeature::class => $this->extractColumnData( $data, ['label', 'desc'], 'items'),
            Building::class =>
                $this->extractColumnData( $data, ['label', 'description','baseVoteText'], 'buildings') &&
                $this->extractNestedArrayData( array_column( $data, 'upgradeTexts' ), 'buildings'),
            CitizenHomeLevel::class => $this->extractColumnData( $data, 'label', 'buildings'),
            CitizenHomeUpgrade::class => $this->extractColumnData( $data, ['label','desc'], 'buildings'),
            CitizenStatus::class,
            Picto::class => $this->extractColumnData( $data, ['label','description'], 'game'),
            CitizenDeath::class,
            CitizenProfession::class => $this->extractColumnData( $data, ['label','desc'], 'game'),
            CitizenRole::class => $this->extractColumnData( $data, ['label','message'], 'game'),
            Ruin::class => $this->extractColumnData( $data, ['label','desc','explorable_desc'], 'game'),
            ZoneTag::class => $this->extractColumnData( $data, 'label', 'game'),
            Town::class => $this->extractColumnData( $data, ['label', 'help'], 'game'),
            Log::class,
            CitizenComplaint::class => $this->extractColumnData( $data, 'text', 'game'),
            GazetteEntry::class => $this->extractColumnData( $data, 'text', 'gazette'),
            HeroSkill::class =>
                $this->extractColumnData( $data, ['title','description','group'], 'game') &&
                $this->extractNestedArrayData( array_column( $data, 'bullets' ), 'game'),
            ForumThreadTag::class => $this->extractColumnData( $data, 'label', 'global'),
            CouncilEntry::class => $this->extractColumnData( $data, 'text', 'council'),
            default => true,
        };
    }

    protected function extractColumnDataAndHandleFemaleTitles( array $data, array|string $columns, string $domain ): bool {
        return array_reduce(
            array_map(
                fn($column) => $this->extractArrayDataForTitles( array_column( $data, $column ), $domain ),
                is_array($columns) ? $columns : [$columns]
            ),
            fn($c,$r) => $r && $c,
            true
        );
    }

    protected function extractArrayDataForTitles( array $data, string $domain ): bool {
        foreach ( array_filter($data) as $message ){
            $this->addMessageToCatalogue($message, $domain, 0);
            $this->addMessageToCatalogue($message."_f", $domain, 0);
        }
        return true;
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
