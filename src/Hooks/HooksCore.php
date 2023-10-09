<?php

namespace App\Hooks;

use Symfony\Contracts\Translation\TranslatorInterface;

class HooksCore {
	protected TranslatorInterface $translator;
	public function __construct(TranslatorInterface $trans) {
		$this->translator = $trans;
	}
}