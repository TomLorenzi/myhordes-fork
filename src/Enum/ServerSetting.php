<?php

namespace App\Enum;

enum ServerSetting: int {
    case None = 0;
    case DisableAutomaticUserValidationMails = 1;

    public function type(): string {
        return match($this) {
            self::None => 'void',
            self::DisableAutomaticUserValidationMails => 'bool',
        };
    }

    public function default(): mixed {
        return match($this) {
            self::None => null,
            self::DisableAutomaticUserValidationMails => false,
        };
    }

    /**
     * @throws \Exception
     */
    public function encodeValue(mixed $value): array {
        return match ($this->type()) {
            'void' => throw new \Exception('Attempt to encode void server setting.'),
            'bool' => ['value' => !!$value]
        };
    }

    /**
     * @throws \Exception
     */
    public function decodeValue(array $value): mixed {
        return match ($this->type()) {
            'void' => throw new \Exception('Attempt to decode void server setting.'),
            'bool' => !!($value['value'] ?? false)
        };
    }
}