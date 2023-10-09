<?php

namespace App\Twig;

use App\Entity\Hook;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ExtensionsRuntime implements RuntimeExtensionInterface {
	protected TranslatorInterface $translator;
	protected UrlGeneratorInterface $router;
	protected Packages $assets;
	protected EntityManagerInterface $entityManager;

	/**
	 * @param TranslatorInterface   $translator
	 * @param UrlGeneratorInterface $router
	 * @param Packages      $assets
	 */
	public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $router, Packages $assets, EntityManagerInterface $entityManager) {
		$this->translator = $translator;
		$this->router = $router;
		$this->assets = $assets;
		$this->entityManager = $entityManager;
	}

	public function execute_hooks(string $hookName, ...$args): string {
		$output = '';

		$registeredHooks = $this->entityManager->getRepository(Hook::class)->findBy(['hookname' => $hookName, 'active' => true]);
		if (count($registeredHooks) === 0) return '';

		usort($registeredHooks, fn($a, $b) => $b->getPosition() <=> $a->getPosition());
		$hookFunction = 'hook' . ucfirst($hookName);
		foreach ($registeredHooks as $registeredHook) {

			if (!class_exists($registeredHook->getClassname())) continue;

			$className = $registeredHook->getClassname();
			$hook = new $className($this->translator, $this->router, $this->assets);
			if (!is_callable([$hook, $hookFunction])) continue;

			$output .= $hook->{$hookFunction}($args);
		}
		return $output;
	}


}