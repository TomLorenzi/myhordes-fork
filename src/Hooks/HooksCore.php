<?php

namespace App\Hooks;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HooksCore {
	protected TranslatorInterface $translator;
	protected UrlGeneratorInterface $router;
	protected Packages $assets;
	public function __construct(TranslatorInterface $trans, UrlGeneratorInterface $router, Packages $assets) {
		$this->translator = $trans;
		$this->router = $router;
		$this->assets = $assets;
	}
}