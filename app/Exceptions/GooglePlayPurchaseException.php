<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class GooglePlayPurchaseException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 422,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }
}