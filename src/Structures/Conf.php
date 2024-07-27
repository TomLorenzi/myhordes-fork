<?php


namespace App\Structures;

use Adbar\Dot;
use App\Enum\Configuration\Configuration;
use Exception;

class Conf
{
    private array $data;
    private ?Dot $dot = null;
    private bool $is_complete = false;

    private function deep_merge( array &$base, array $inc ): void
    {
        foreach ($inc as $key => $data) {
            if (!isset($base[$key])) $base[$key] = $data;
            elseif ( is_array( $base[$key] ) === is_array( $data ) ) {
                if (is_array($data) && array_keys($data) === ['replace'])  $base[$key] = $data['replace'];
                elseif (is_array($data) && array_keys($data) === ['merge'])  $base[$key] = array_merge( $base[$key], $data['merge'] );
                elseif (is_array($data) && array_keys($data) === ['remove']) $base[$key] = array_filter( $base[$key], fn($e) => !in_array($e, $data['remove']) );
                elseif (is_array($data)) $this->deep_merge( $base[$key], $data );
                else $base[$key] = $data;
            }
        }
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function import( array $data ): self {
        $this->is_complete = false;
        $this->deep_merge( $this->data, $data );
        return $this;
    }

    public function complete(): self {
        if ($this->is_complete) return $this;
        $this->is_complete = true;
        $this->dot = new Dot($this->data);
        return $this;
    }

    public function raw(): array {
        return $this->dot?->flatten() ?? (new Dot($this->data))->flatten();
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string|Configuration $key, $default = null) {
        if ( is_a( $key, Configuration::class ) ) {

            if ($key->abstract()) throw new Exception("Cannot read data from abstract setting '{$key->name()}'.");
            return $this->dot->get( $key->key(), $key->default() ?? $default );

        } else return $this->dot->get( $key, $default );
    }

    /**
     * @throws Exception
     * @deprecated
     */
    public function getSubKey(string $key, string $subKey, $default = null) {
        return $this->get( "{$key}.{$subKey}", $default );
    }

    /**
     * @throws Exception
     */
    public function is(string $key, $values, $default = null): bool {
        return is_array( $values )
            ? in_array( $this->get($key,$default), $values )
            : $this->get($key,$default) === $values;
    }
}
