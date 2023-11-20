<?php

namespace App\Twig;

use App\Service\HookExecutor;
use Twig\Extension\RuntimeExtensionInterface;

class ExtensionsRuntime implements RuntimeExtensionInterface {
	protected HookExecutor $hookExecutor;
	/**
	 * @param HookExecutor $hookExecutor
	 */
	public function __construct(HookExecutor $hookExecutor) {
		$this->hookExecutor = $hookExecutor;
	}

	public function execute_hooks(string $hookName, ...$args): string {
		$hookArgs = [];
		foreach ($args as $argName => $arg) {
			$hookArgs[$argName] = $arg;
		}
		return $this->hookExecutor->execute_hooks($hookName, $hookArgs);
	}


}