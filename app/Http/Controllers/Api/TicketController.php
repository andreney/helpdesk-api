<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * Cria um novo ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->create(
            $request->user(),
            $request->validated()
        );

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Lista os tickets.
     */
    public function index()
    {
        $tickets = $this->ticketService->getAll();

        return TicketResource::collection($tickets);
    }

    /**
     * Exibe um ticket específico.
     */
    public function show(int $id): TicketResource
    {
        $ticket = $this->ticketService->findById($id);

        if (! $ticket) {
            abort(404, 'Ticket não encontrado.');
        }

        return new TicketResource($ticket);
    }

    /**
     * Atualiza o status de um ticket.
     */
    public function updateStatus(
        UpdateTicketStatusRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $ticket = $this->ticketService->updateStatus(
            $ticket,
            $request->validated()['status']
        );

        return response()->json($ticket);
    }
}
