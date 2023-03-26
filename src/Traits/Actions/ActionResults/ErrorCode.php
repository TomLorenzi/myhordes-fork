<?php

namespace App\Traits\Actions\ActionResults;

trait ErrorCode
{
    private ?int $error = null;
    private ?\Throwable $exception = null;
    public function error(): ?int { return $this->error; }
    public function exception(): ?\Throwable { return $this->exception; }
    public function hasError(): bool { return $this->error != 0; }
}