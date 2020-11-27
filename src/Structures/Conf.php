<?php


namespace App\Structures;

class Conf
{
    private array $data;
    private array $flat = [];
    private bool $is_complete = false;

    private function deep_merge( array &$base, array $inc ) {
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

    private function flatten(array &$data, ?array &$lines = [], string $prefix = '' ) {
        foreach ($data as $key => $entry) {
            $go_deeper = is_array($entry) && !empty($entry) && count(array_filter( array_keys( $entry ), function ($k) { return is_numeric($k); } )) !== count($entry);

            $current_key = empty($prefix) ? $key : "{$prefix}.{$key}";
            if ($go_deeper) $this->flatten( $entry, $lines, $current_key );
            else $lines[$current_key] = $entry;
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
        $this->flat = [];
        $this->flatten($this->data, $this->flat);
        return $this;
    }

    public function raw(): array {
        return $this->flat;
    }

    public function getData() {
        return $this->data;
    }

    public function get(string $key, $default = null) {
        return $this->flat[$key] ?? $default;
    }
}
