<?php

namespace App\Hooks;

class HookRegistry {
	public const HOOK_NOT_CALLED = 'notCalled';
	public const HOOK_CALLED = 'called';

	/**
	 * @var array the current selected hook during the request
	 */
	private array $currentHook = [];

	/**
	 * @var array the list of hooks data
	 */
	private array $hooks;

	public function __construct()
	{
		$this->hooks = [
			self::HOOK_CALLED => [],
			self::HOOK_NOT_CALLED => [],
		];
	}

	/**
	 * @param string $hookName THe hookname that was triggered
	 * @param array  $hookArguments The arguments sent to the hook
	 * @param string $file filepath where the "Hook::exec" call have been done
	 * @param int    $line position in file where the "Hook::exec" call have been done
	 */
	public function selectHook(string $hookName, array $hookArguments, string $file, int $line): void{
		$this->currentHook = [
			'name' => $hookName,
			'args' => $hookArguments,
			'location' => "$file:$line",
			'status' => self::HOOK_NOT_CALLED,
			'handlers' => []
		];
	}

	public function hookHandledBy(string $className, int $position, bool $isActive): void {
		$this->currentHook['handlers'][$className] = [
			'classname' => $className,
			'position' => $position,
			'active' => $isActive
		];
	}

	/**
	 * Notify the registry that the selected hook have been called.
	 */
	public function hookWasCalled(): void{
		$this->currentHook['status'] = self::HOOK_CALLED;
	}

	/**
	 * @return array the list of called hooks
	 */
	public function getCalledHooks(): array{
		return $this->hooks['called'];
	}

	/**
	 * @return array the list of uncalled hooks
	 */
	public function getNotCalledHooks(): array	{
		return $this->hooks['notCalled'];
	}

	/**
	 * @return array the list of dispatched hooks
	 */
	public function getHooks(): array {
		return array_merge_recursive($this->hooks['called'], $this->hooks['notCalled']);
	}

	/**
	 * Persist the selected hook into the list.
	 * These hooks will be used by the HookDataCollector
	 */
	public function collect(): void {
		$name = $this->currentHook['name'];
		$status = $this->currentHook['status'];

		$hook = [
			'args' => $this->currentHook['args'],
			'name' => $name,
			'location' => $this->currentHook['location'],
			'status' => $status,
			'handlers' => $this->currentHook['handlers'],
		];

		$this->hooks[$status][$name][] = $hook;
	}
}