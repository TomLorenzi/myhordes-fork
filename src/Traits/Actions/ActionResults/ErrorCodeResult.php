<?php

namespace App\Traits\Actions\ActionResults;

trait ErrorCodeResult
{
    use ErrorCode;
    public function withError(int $error, \Throwable $exception = null): self {
        $this->error = $error;
        $this->exception = $exception;
        return $this;
    }
}