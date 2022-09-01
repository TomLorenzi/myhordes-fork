<?php


namespace App\Twig;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use League\CommonMark\CommonMarkConverter;

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
        $parser = new CommonMarkConverter([]);
        $string = preg_replace('#<p>(.*)</p>#i', '$1', $parser->convert($string));
        return trim($string);
    }
}