<?php

namespace Monastirevrf\DeliveryService\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DeliveriesException extends \Exception
{
    public function __construct($message, $code = null, Throwable $previous = null)
    {
        parent::__construct($message, $code ?? self::Response::HTTP_BAD_REQUEST, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}