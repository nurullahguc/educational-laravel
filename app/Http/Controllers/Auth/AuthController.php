<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Shared session-based (Sanctum SPA) authentication for every project in this
 * backend. One account works across all modules; nothing here is TicketFlow
 * specific.
 */
class AuthController extends Controller
{
    /**
     * Register a new user and immediately log them in.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // hashed via the User 'password' cast
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Log the user in via session cookie and rotate the session id.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return UserResource::make($request->user())->response();
    }

    /**
     * Invalidate the current session and CSRF token.
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * Return the currently authenticated user.
     */
    public function currentUser(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
