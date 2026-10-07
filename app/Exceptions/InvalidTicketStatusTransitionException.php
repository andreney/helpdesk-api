<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTicketStatusTransitionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Transição de status do ticket não permitida.');
    }
}
