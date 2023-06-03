<?php

namespace App\Enum;

enum SortDefinitionWord: string
{
    case Default = 'default';

    case Start = 'start';
    case End = 'end';

    case Before = 'before';
    case After = 'after';

    public function stable(): bool {
        return match($this) {
            self::Default, self::Start, self::End => true,
            default => false
        };
    }
}
