<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can register with valid data and is logged in', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Nurullah Güç',
        'email' => '  NEW@Example.com ',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Nurullah Güç')
        ->assertJsonPath('data.email', 'new@example.com') // trimmed + lowercased
        ->assertJsonMissingPath('data.password');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});

test('registration validation rejects invalid data', function () {
    $this->postJson('/api/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('a user can login with correct credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonPath('data.email', 'user@example.com');

    $this->assertAuthenticated();
});

test('login is rejected with a wrong password and a generic message', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    $this->assertGuest();
});

test('an authenticated user can fetch their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonMissingPath('data.password');
});

test('a guest cannot access protected endpoints', function () {
    $this->getJson('/api/user')->assertUnauthorized();
    $this->getJson('/api/tickets')->assertUnauthorized();
});

test('logout ends the session', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    // Log in for real so an actual session is established.
    $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'password123',
    ])->assertOk();

    expect(auth()->guard('web')->check())->toBeTrue();

    // Logging out returns 204 and clears the authenticated session.
    $this->postJson('/api/logout')->assertNoContent();

    expect(auth()->guard('web')->check())->toBeFalse();
});
