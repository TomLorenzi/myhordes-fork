<?php

namespace App\Service\Actions\Game;

use App\Entity\CitizenProperties;

readonly class DecodeConditionalMessageAction
{
    public function __construct(

    ) { }

    private static function matchBooleanTag(string $tagValue, array $booleanTags): bool {
        return in_array($tagValue, $booleanTags);
    }

    private static function matchCitizenProperty(string $tagValue, array $tagArgs, ?CitizenProperties $properties = null): bool {
        if ($properties === null) return false;

        $prop = \App\Enum\Configuration\CitizenProperties::fromName( $tagValue );
        if (!$prop) return false;

        ['op' => $op, 'val' => $val] = [
            'op' => 'is',
            'val' => 'null',
            ...$tagArgs,
        ];

        $val = json_validate($val) ? json_decode( $val, true ) : $val;
        $stored = $properties->get( $prop );

        return match ($op) {
            'is' => $stored === $val,
            'in' => is_array($stored) && in_array($val, $stored),
            'gt' => is_numeric($stored) && $stored > $val,
            'gte' => is_numeric($stored) && $stored >= $val,
            'lt' => is_numeric($stored) && $stored < $val,
            'lte' => is_numeric($stored) && $stored <= $val,
            default => false
        };
    }

    private static function processTag(
        string $tagType, string $tagValue, array $tagArgs, string $content,
        array $booleanTags = [],
        ?CitizenProperties $properties = null,
    ): string
    {
        $inverse = str_starts_with($tagType, 'n');
        $tagType = $inverse ? substr($tagType, 1) : $tagType;

        $match = match ($tagType) {
            '1' => true,
            't' => self::matchBooleanTag($tagValue, $booleanTags),
            'c' => self::matchCitizenProperty($tagValue, $tagArgs, $properties),
            default => false,
        };

        return match(true) {
            $inverse => $match ? '' : $content,
            default => $match ? $content : '',
        };
    }

    public function __invoke(
        string $baseMessage,
        array $booleanTags = [],
        ?CitizenProperties $properties = null,
    ): string
    {
        do {
            $baseMessage = preg_replace_callback( '/<(.*?)-(.*?)(?:\s(.*?=".*?"))?>(.*?)<\/\1-\2>/' , function(array $m) use ($booleanTags, $properties): string {
                [, $tagType, $tagValue, $tagArgs, $text] = $m;
                $tagArgs = array_map(fn(string $a) => explode('=', $a), array_filter( explode(' ', $tagArgs ?? ''), fn($a) => !empty($a)));
                return self::processTag( $tagType, $tagValue, array_combine( array_map( fn(array $a) => $a[0], $tagArgs ), array_map( fn(array $a) => substr($a[1], 1, -1), $tagArgs ) ), $text, $booleanTags, $properties );
            }, $baseMessage, -1, $c);
        } while ($c > 0);

        return $baseMessage;
    }
}