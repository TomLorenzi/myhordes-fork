<?php


namespace App\Twig;


use DateTime;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extensions extends AbstractExtension  implements GlobalsInterface
{
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $router;

    public function __construct(TranslatorInterface $ti, UrlGeneratorInterface $r) {
        $this->translator = $ti;
        $this->router = $r;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('instance_of', [$this, 'instance_of']),
            new TwigFilter('to_date',  [$this, 'create_date']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('instance_of', [$this, 'instance_of']),
            new TwigFunction('to_date',  [$this, 'create_date']),
            new TwigFunction('help_btn', [$this, 'help_btn'], ['is_safe' => array('html')]),
            new TwigFunction('help_lnk', [$this, 'help_lnk'], ['is_safe' => array('html')]),
        ];
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function instance_of($object, $classname): bool {
        return is_a($object, $classname);
    }

    /**
     * @param string $object
     * @return DateTime
     * @throws Exception
     */
    public function create_date(string $object): DateTime {
        return new DateTime($object);
    }

    public function help_btn(string $tooltipContent): string {
        return "<a class='help-button'><div class='tooltip help'>$tooltipContent</div>" . $this->translator->trans("Hilfe", [], "global") . "</a>";
    }

    public function help_lnk(string $name, string $controller = null, array $args = []): string {
        $link = $controller !== null ? $this->router->generate($controller, $args) : "";
        return "<span class='helpLink'>" . $this->translator->trans("Spielhilfe:", [], "global") . " <a class='link' x-ajax-href='$link' target='_blank'>$name</a></span>";
    }
}