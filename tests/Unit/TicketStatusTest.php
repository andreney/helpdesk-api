<?php

use App\Enums\TicketStatus;

test('cada status permite apenas as transições previstas', function (TicketStatus $from, array $allowed) {
    expect($from->allowedTransitions())->toBe($allowed);
})->with([
    'OPEN' => [TicketStatus::OPEN, [TicketStatus::IN_PROGRESS]],
    'IN_PROGRESS' => [TicketStatus::IN_PROGRESS, [TicketStatus::WAITING, TicketStatus::CLOSED]],
    'WAITING' => [TicketStatus::WAITING, [TicketStatus::IN_PROGRESS]],
    'CLOSED' => [TicketStatus::CLOSED, []],
]);

test('canTransitionTo reflete a matriz de transições', function () {
    expect(TicketStatus::OPEN->canTransitionTo(TicketStatus::IN_PROGRESS))->toBeTrue()
        ->and(TicketStatus::OPEN->canTransitionTo(TicketStatus::CLOSED))->toBeFalse()
        ->and(TicketStatus::CLOSED->canTransitionTo(TicketStatus::OPEN))->toBeFalse();
});

test('nenhum status transiciona para si mesmo', function () {
    foreach (TicketStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});
