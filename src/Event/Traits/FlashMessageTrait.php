<?php

namespace App\Event\Traits;

trait FlashMessageTrait
{
    protected array $flash_messages = [];

    public function addFlashMessage(string $message, string $type = 'notice', string $domain = 'game', array $args = [], bool $conditional_success = false): void {
        $this->flash_messages[] = [$type,$message,$domain,$args,$conditional_success];
    }

    public function getFlashMessages(): array {
        return $this->flash_messages;
    }
}