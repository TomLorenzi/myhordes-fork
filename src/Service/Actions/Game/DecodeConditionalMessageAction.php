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

    private static function matchCitizenProperty(string $tagValue, array $tagArgs, ?CitizenProperties $properties = null, string &$content = ''): bool {
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


        $mapProps = $op === 'map'
            ? array_filter( array_keys( $tagArgs ), fn(string $t) => !in_array( $t, ['op', 'val', 'glue', 'final_glue'] ) )
            : [];

        $matches = match ($op) {
            'is' => $stored === $val,
            'in' => is_array($stored) && in_array($val, $stored),
            'gt' => is_numeric($stored) && $stored > $val,
            'gte' => is_numeric($stored) && $stored >= $val,
            'lt' => is_numeric($stored) && $stored < $val,
            'lte' => is_numeric($stored) && $stored <= $val,
            'map' => is_array($stored) && !empty( array_intersect( $stored, $mapProps ) ),
            default => false
        };

        if ($matches && $op === 'map' && is_array($stored)) {
            $map = array_values( array_filter( array_map( fn(string $k) => in_array($k, $mapProps) ? ($tagArgs[$k] ?? null) : null, $stored ), fn(?string $k) => $k !== null ) );
            if (count( $map ) <= 1) $content = implode( $tagArgs['glue'] ?? ', ', $map );
            else $content =
                implode( $tagArgs['glue'] ?? ', ', array_slice($map, 0, -1) ) .
                ($tagArgs['final_glue'] ?? $tagArgs['glue'] ?? ', ') . $map[count($map) - 1];
        }

        return $matches;
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
            'c' => self::matchCitizenProperty($tagValue, $tagArgs, $properties, $content),
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

                $args = [];
                while (($next_tag = strstr($tagArgs, '="', true)) !== false) {
                    $tagArgs = mb_substr($tagArgs, mb_strlen($next_tag) + 2);
                    $next_value = strstr($tagArgs, '"', true);
                    if ($next_value !== false) {
                        $args[trim($next_tag)] = $next_value;
                        $tagArgs = trim(mb_substr($tagArgs, mb_strlen($next_value) + 1));
                    }
                }
                return self::processTag( $tagType, $tagValue, $args, $text, $booleanTags, $properties );
            }, $baseMessage, -1, $c);
        } while ($c > 0);

        return $baseMessage;
    }
}