<?php

namespace App\Service;

use App\Entity\Hook;
use App\Hooks\HookRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class HookExecutor {
	protected TranslatorInterface $translator;
	protected UrlGeneratorInterface $router;
	protected Packages $assets;
	protected EntityManagerInterface $entityManager;
	protected Environment $twig;
	protected TokenStorageInterface $token;
	protected ContainerInterface $container;
	protected HookRegistry $hookRegistry;

	/**
	 * @param TranslatorInterface    $translator
	 * @param UrlGeneratorInterface  $router
	 * @param Packages               $assets
	 * @param EntityManagerInterface $entityManager
	 * @param Environment            $twig
	 * @param TokenStorageInterface  $token
	 * @param ContainerInterface     $container
	 * @param HookRegistry      	 $hookRegistry
	 */
	public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $router, Packages $assets, EntityManagerInterface $entityManager, Environment $twig, TokenStorageInterface $token, ContainerInterface $container, HookRegistry $hookRegistry) {
		$this->translator = $translator;
		$this->router = $router;
		$this->assets = $assets;
		$this->entityManager = $entityManager;
		$this->twig = $twig;
		$this->token = $token;
		$this->container = $container;
		$this->hookRegistry = $hookRegistry;
	}

	public function execute_hooks(string $hookName, array $args = []): string {
		$output = '';

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		$this->hookRegistry->selectHook($hookName, $args, $backtrace[0]['file'], $backtrace[0]['line']);

		$registeredHooks = $this->entityManager->getRepository(Hook::class)->findBy(['hookname' => $hookName]);
		if (count($registeredHooks) === 0) return '';

		usort($registeredHooks, fn($a, $b) => $a->getPosition() <=> $b->getPosition());

		foreach ($registeredHooks as $registeredHook) {

			$this->hookRegistry->hookHandledBy($registeredHook->getClassname(), $registeredHook->getPosition(), $registeredHook->isActive());

			if (!class_exists($registeredHook->getClassname())) {
				continue;
			}

			$className = $registeredHook->getClassname();
			$hook = new $className($this->translator, $this->router, $this->assets, $this->twig, $this->token, $this->container);

            $hookFunction = $registeredHook->getFuncName() ?? ('hook' . ucfirst($hookName));
			if (!is_callable([$hook, $hookFunction])) {
				continue;
			}

			if (!$registeredHook->isActive()) {
				continue;
			}

			$output .= $hook->{$hookFunction}($args);
			$this->hookRegistry->hookWasCalled();
		}
		$this->hookRegistry->collect();
		return $output;
	}
}