<?php

namespace App\Event\Traits;

trait FlashMessageTrait
{
    protected array $flash_messages = [];

    public function addFlashMessage(string $message, string $domain = 'game', array $args = []): void {
        $this->flash_messages[] = [$message,$domain,$args];
    }

    public function getFlashMessages(): array {
        return $this->flash_messages;
    }
}