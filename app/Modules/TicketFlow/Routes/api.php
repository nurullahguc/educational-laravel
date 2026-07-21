<?php

use App\Modules\TicketFlow\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
| TicketFlow API routes. Loaded by TicketFlowServiceProvider inside the "api"
| middleware group with the "/api" prefix. All ticket endpoints require an
| authenticated Sanctum SPA session.
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tickets', TicketController::class)
        ->parameters(['tickets' => 'ticket'])
        ->except(['create', 'edit']);
});
