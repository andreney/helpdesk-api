<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
    public function index(ListTicketsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $tickets = $this->ticketService->getPaginated(
            $request->user(),
            [
                'status' => $request->validated('status'),
                'search' => $request->validated('search'),
                'mine' => $request->boolean('mine'),
            ],
            $request->integer('per_page', 15)
        );

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

        Gate::authorize('view', $ticket);

        return new TicketResource($ticket);
    }

    /**
     * Atualiza o status de um ticket.
     */
    public function updateStatus(
        UpdateTicketStatusRequest $request,
        Ticket $ticket
    ): TicketResource {
        // A autorização (policy updateStatus) é feita no UpdateTicketStatusRequest.
        $ticket = $this->ticketService->updateStatus(
            $ticket,
            $request->validated()['status'],
            $request->user()
        );

        return new TicketResource($ticket);
    }

    /**
     * Contagem de tickets por status, para os cards de resumo.
     */
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        return response()->json([
            'data' => $this->ticketService->getSummary(
                $request->user(),
                $request->boolean('mine')
            ),
        ]);
    }
}
