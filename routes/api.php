<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared API routes
|--------------------------------------------------------------------------
|
| These endpoints are shared by every project in this backend. Authentication
| is session/cookie based (Sanctum SPA): the frontend first calls
| GET /sanctum/csrf-cookie, then POST /api/login or /api/register.
|
| Per-project routes live inside each module and are registered by that
| module's service provider (see app/Modules/TicketFlow/Routes/api.php).
|
*/

// Guest endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'currentUser']);
});
