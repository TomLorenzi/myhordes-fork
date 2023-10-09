<?php

namespace App\Hooks;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class HooksCore {
	protected TranslatorInterface $translator;
	protected UrlGeneratorInterface $router;
	protected Packages $assets;
	protected Environment $twig;
	public function __construct(TranslatorInterface $trans, UrlGeneratorInterface $router, Packages $assets, Environment $twig) {
		$this->translator = $trans;
		$this->router = $router;
		$this->assets = $assets;
		$this->twig = $twig;
	}
}