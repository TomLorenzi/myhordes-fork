<?php


namespace App\Service\Globals;


use App\Enum\ClientSignal;

class ResponseGlobal
{
    protected array $signals = [];

    public function withSignal(ClientSignal $signal, ...$signals): void {
        $this->signals = [
            ...$this->signals,
            $signal->value,
            ...array_map(fn(ClientSignal $s) => $s->value, $signals)
        ];
    }

    public function withConditionalSignal(mixed $condition, ClientSignal $signal, ...$signals): void {
        if (!!$condition) $this->withSignal( $signal, ...$signals );
    }

    public function getSignals(): array {
        return $this->signals = array_unique($this->signals);
    }
}