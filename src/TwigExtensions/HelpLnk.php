<?php

namespace App\TwigExtensions;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HelpLnk extends AbstractExtension
{
    private TranslatorInterface $translator;
    private UrlGeneratorInterface  $router;

    public function __construct(TranslatorInterface $ti, UrlGeneratorInterface $r) {
        $this->translator = $ti;
        $this->router = $r;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('help_lnk', [$this, 'help_lnk'], ['is_safe' => array('html')]),
        ];
    }

    public function help_lnk(string $name, string $controller = null, array $args = []): string
    {
        $link = $controller !== null ? $this->router->generate($controller, $args) : "";

        return "<span class='helpLink'>" . $this->translator->trans("Spielhilfe:", [], "global") . " <a class='link' x-ajax-href='$link' target='_blank'>$name</a></span>";
    }
 }