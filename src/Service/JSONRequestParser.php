<?php


namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class JSONRequestParser
{
    protected $data = null;
    protected $trimmed_data = null;

    protected function deep_trim( &$value ): void {
        // Remove all space characters (based on the Unicode "Space Separator - Zs" category ) from the beginning and end of the string
        if (is_string($value)) $value = preg_replace('/^\p{Zs}+|\p{Zs}+$/u', '', $value);
        else if (is_array($value) || is_object($value)) foreach ($value as &$v) $this->deep_trim( $v );
    }

    public function __construct()
    {
        $request = Request::createFromGlobals();
        $ct = $request->getContentTypeFormat();
        if ($ct === 'json') {
            $this->trimmed_data = $this->data =
                json_decode($request->getContent(), true, 512, JSON_INVALID_UTF8_IGNORE );
            $this->deep_trim( $this->trimmed_data );
        }

    }

    public function valid( ): bool {
        return $this->data !== null;
    }

    public function inject( string $key, $data ): void {
        $this->data[$key] = $data;
        $this->deep_trim($data);
        $this->trimmed_data[$key] = $data;
    }

    public function has( string $key, bool $not_empty = false ): bool {
        return isset( $this->data[$key] ) && ( !$not_empty || !empty( $this->data[$key] ) );
    }

    public function has_all( array $keys, bool $not_empty = false ): bool {
        foreach ($keys as &$key) if (!$this->has( $key, $not_empty )) return false;
        return true;
    }

    public function get( string $key, $default = null, ?array $from = null ) {
        return $this->has( $key ) ? ( $from === null || in_array($this->data[$key], $from) ? $this->data[$key] : $default) : $default;
    }

    public function get_array( string $key, $default = [] ): array {
        $v = $this->get($key, $default);
        return is_array($v) ? $v : $default;
    }

    public function get_dateTime( string $key, \DateTimeInterface $default = null): ?\DateTimeInterface {
        if (!$this->has($key)) return $default;
        try {
            return new \DateTime($this->get($key));
        } catch (\Throwable) { return $default; }
    }

    protected function bool_to_int($v): mixed {
        if ($v === true) return 1;
        elseif ($v === false) return 0;
        else return $v;
    }

    /**
     * @param string $key
     * @param int|float $default
     * @return int|float
     */
    public function get_num( string $key, $default = -1 ) {
        $v = $this->bool_to_int( $this->get($key, $default) );
        return is_numeric($v) ? $v : $default;
    }

    public function get_int( string $key, ?int $default = -1, ?int $min = null, ?int $max = null ): ?int {
        $v = $this->bool_to_int( $this->get($key, $default) );
        if (!is_numeric($v)) return $default;
        $v = intval($v);
        return (($min !== null && $v < $min) || ($max !== null && $v > $max)) ? $default : $v;
    }


    /**
     * @param string $key
     * @param string $class The name of the event class.
     * @psalm-param class-string<T> $class
     * @param mixed|null $default
     * @psalm-param T|null $default
     *
     * @return \BackedEnum The repository class.
     * @psalm-return T
     *
     * @template T as \BackedEnum
     */

    public function get_enum(string $key, string $class, mixed $default = null ) {
        return $this->has( $key ) ? ($class::tryFrom( $this->get($key) )) : $default;
    }

    public function get_base64( string $key, $default = null ) {
        return $this->has( $key ) ? base64_decode($this->data[$key], true) : $default;
    }

    public function trimmed( string $key, $default = null, ?array $from = null ) {
        return $this->has( $key ) ? ( $from === null || in_array($this->trimmed_data[$key], $from) ? $this->trimmed_data[$key] : $default) : $default;
    }

    public function all( bool $trimmed = false ) {
        return $trimmed ? $this->trimmed_data : $this->data;
    }

}