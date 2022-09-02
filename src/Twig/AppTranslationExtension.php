<?php


namespace App\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppTranslationExtension extends AbstractExtension {
    private TranslatorInterface $translator;
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array {
        return [
            new TwigFilter('trans', $this->trans(...), ['is_safe'=>['html']]),
        ];
    }

    public function trans(string|\Stringable|TranslatableInterface|null $message, array|string $arguments = [], string $domain = null, string $locale = null, int $count = null): string {
        $string = $this->translator->trans($message, $arguments, $domain, $locale);

        $config = [
            'html_input' => "allow"
        ];
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());

        $converter = new MarkdownConverter($environment);
        $string = $converter->convert($string);
        $string = preg_replace('#<p>(.*)</p>#i', '$1', $string);
        $string = html_entity_decode($string);
        return trim($string);
    }
}