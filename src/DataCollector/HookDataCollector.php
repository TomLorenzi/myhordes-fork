<?php

namespace App\DataCollector;

use App\Hooks\HookRegistry;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HookDataCollector extends AbstractDataCollector {
	/**
	 * @var HookRegistry
	 */
	private HookRegistry $registry;

	public function __construct(HookRegistry $registry)
	{
		$this->registry = $registry;
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
		$hooks = $this->registry->getHooks();
		$calledHooks = $this->registry->getCalledHooks();
		$notCalledHooks = $this->registry->getNotCalledHooks();
		$this->data = [
			'hooks' => $hooks,
			'calledHooks' => $calledHooks,
			'notCalledHooks' => $notCalledHooks,
		];
	}

	public static function getTemplate(): ?string {
		return 'data_collector/hooks.html.twig';
	}

	public function getHooks(): array {
		return $this->data['hooks'];
	}

	/**
	 * Return the list of every called legacy hooks during one request.
	 *
	 * @return array
	 */
	public function getCalledHooks()
	{
		return $this->data['calledHooks'];
	}

	/**
	 * Return the list of every uncalled legacy hooks during oHookne request.
	 *
	 * @return array
	 */
	public function getNotCalledHooks()
	{
		return $this->data['notCalledHooks'];
	}

}