<?php


namespace App\Twig;

use Jawira\CaseConverter\Convert;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class HTMLUtils extends AbstractExtension  implements GlobalsInterface
{

    public function getFilters(): array
    {
        return [
            new TwigFilter('classes', [$this, 'classListParser'], ['is_safe' => ['html_attr']]),
            new TwigFilter('attributes', [$this, 'attributeListParser'], ['is_safe' => ['html_attr']]),
            new TwigFilter('data', [$this, 'datasetParser'], ['is_safe' => ['html_attr']]),
        ];
    }

    public function getFunctions(): array
    {
        return [];
    }

    public function classListParser( array|string $data ): string {
        if (is_string($data)) return $this->classListParser([$data]);

        $class_list = implode(' ', array_map(fn(string $class) => addslashes($class), array_unique( array_merge(
            // numeric key, string value: the class is the value; if the class is false-ly, discard
            array_values( array_filter( $data, fn($value,$key) => is_int($key) && !is_array($value) && $value, ARRAY_FILTER_USE_BOTH ) ),

            // string key, string value: the class is the key, the value the condition; if the condition is false-ly, discard
            array_keys( array_filter( $data, fn($value,$key) => !is_int($key) && !is_array($value) && $value, ARRAY_FILTER_USE_BOTH ) ),

            // array value: the first array element is the class, the remaining ones are conditions
            array_values( array_filter( array_map( fn(array $conf) => array_reduce( array_slice( $conf, 1 ), fn(bool $carry, bool $item) => $carry && $item, true ) ? $conf[0] : null, array_filter( $data, fn($value) => is_array($value) && !empty($value) ) ) ) ),
        ) ) ) );

        return "class=\"{$class_list}\"";
    }

    public function attributeListParser( array $data ): string {

        $normalized_data = [];
        foreach ( $data as $key => $value )
            if (is_array($value) && !empty($value))
                $normalized_data[htmlentities($value[0])] = $value[1] ?? true;
            elseif ( is_int( $key ) ) $normalized_data[htmlentities($value)] = true;
            elseif ( $value !== false ) $normalized_data[htmlentities($key)] = $value;


        $normalized_data = array_filter( array_map( fn($attribute, $value) => $value === true ? [$attribute,$attribute] : [$attribute,(is_string($value) ? htmlentities($value) : $value)], array_keys($normalized_data), $normalized_data ) );

        return implode(" ", array_map( fn($list) => "{$list[0]}=\"{$list[1]}\"", $normalized_data ));
    }

    public function datasetParser( array $data ): string {
        $converted = [];
        array_walk_recursive($data, function($value, string $key) use (&$converted) {
            $converted[ 'data-' . (new Convert($key))->toKebab() ] = is_string( $value ) ? htmlentities($value) : $value;
        });
        $converted = array_filter( $converted, fn($v) => $v !== null );
        return implode(" ", array_map( fn($key, $value) => "{$key}=\"{$value}\"", array_keys($converted), $converted ));
    }

    public function getGlobals(): array
    {
        return [];
    }
}