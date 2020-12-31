<?php

namespace App\TwigExtensions;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HelpBtn extends AbstractExtension
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $ti) {
        $this->translator = $ti;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('help_btn', [$this, 'help_btn'], ['is_safe' => array('html')]),
        ];
    }

    public function help_btn(string $tooltipContent): string
    {
        return "<a class='help-button'><div class='tooltip help'>$tooltipContent</div>" . $this->translator->trans("Hilfe", [], "global") . "</a>";
    }
 }