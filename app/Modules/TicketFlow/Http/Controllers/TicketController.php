<?php

namespace App\Modules\TicketFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TicketFlow\Http\Requests\IndexTicketRequest;
use App\Modules\TicketFlow\Http\Requests\StoreTicketRequest;
use App\Modules\TicketFlow\Http\Requests\UpdateTicketRequest;
use App\Modules\TicketFlow\Http\Resources\TicketResource;
use App\Modules\TicketFlow\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Every action starts its query from `$request->user()->tickets()`, so a user
 * can only ever touch their own tickets. Requesting another user's ticket id
 * yields a 404 (via findOrFail), which avoids leaking that the record exists.
 * The TicketPolicy is wired up as a second, explicit layer of defense.
 */
class TicketController extends Controller
{
    public function index(IndexTicketRequest $request): AnonymousResourceCollection
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $perPage = (int) $request->input('per_page', 10);
        $id = (int) $request->input('id', null);

        $tickets = $request->user()->tickets()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                // Group the OR search so it never widens the ownership scope.
                $query->where(function ($group) use ($search) {
                    $group->where('title', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('id'), fn ($query) => $query->where('id', $request->input('id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->input('priority')))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $request->user()->tickets()->create($request->validated());

        return TicketResource::make($ticket)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $ticket): TicketResource
    {
        $model = $this->resolveOwnedTicket($request, $ticket);
        $this->authorize('view', $model);

        return TicketResource::make($model);
    }

    public function update(UpdateTicketRequest $request, int $ticket): TicketResource
    {
        $model = $this->resolveOwnedTicket($request, $ticket);
        $this->authorize('update', $model);

        $model->update($request->validated());

        return TicketResource::make($model);
    }

    public function destroy(Request $request, int $ticket): Response
    {
        $model = $this->resolveOwnedTicket($request, $ticket);
        $this->authorize('delete', $model);

        $model->delete();

        return response()->noContent();
    }

    /**
     * Resolve a ticket that belongs to the current user, or 404.
     */
    private function resolveOwnedTicket(Request $request, int $id): Ticket
    {
        return $request->user()->tickets()->findOrFail($id);
    }
}
