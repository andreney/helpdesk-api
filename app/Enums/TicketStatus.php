<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING = 'WAITING';
    case CLOSED = 'CLOSED';

    /**
     * Status para os quais este status pode evoluir.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::OPEN => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::WAITING, self::CLOSED],
            self::WAITING => [self::IN_PROGRESS],
            self::CLOSED => [],
        };
    }

    /**
     * Indica se a transição para o status informado é permitida.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
