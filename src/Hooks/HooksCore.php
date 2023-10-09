<?php

namespace App\Hooks;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class HooksCore {
	protected TranslatorInterface $translator;
	protected UrlGeneratorInterface $router;
	protected Packages $assets;
	protected Environment $twig;
	protected TokenStorageInterface $tokenStorage;
	protected ContainerInterface $container;

	public function __construct(TranslatorInterface $trans, UrlGeneratorInterface $router, Packages $assets, Environment $twig, TokenStorageInterface $tokenStorage, ContainerInterface $container) {
		$this->translator = $trans;
		$this->router = $router;
		$this->assets = $assets;
		$this->twig = $twig;
		$this->tokenStorage = $tokenStorage;
		$this->container = $container;
	}
}