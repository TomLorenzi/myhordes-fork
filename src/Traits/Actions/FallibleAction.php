<?php

namespace App\Traits\Actions;

use App\Service\ErrorHelper;
use App\Traits\Actions\ActionResults\ErrorCode;
use App\Traits\Actions\ActionResults\ErrorCodeResult;
use App\Traits\Actions\ActionResults\Optional;
use Doctrine\DBAL\Exception;
use Throwable;

trait FallibleAction
{
    /**
     * @param int|null $errorCode Error code to sent. If omitted, will be deduced from $exception or set to InternalError
     * @param Throwable|null $exception Exception to log
     * @return ErrorCode
     */
    protected function error(int $errorCode = null, Throwable $exception = null): object
    {
        return (new class { use Optional, ErrorCodeResult; })->withError( $errorCode ?? match (true) {
            is_a( $exception, Exception::class) => ErrorHelper::ErrorDatabaseException,
            default => null,
        } ?? ErrorHelper::ErrorInternalError, $exception );
    }
}