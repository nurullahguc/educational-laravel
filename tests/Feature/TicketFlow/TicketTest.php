<?php

use App\Models\User;
use App\Modules\TicketFlow\Enums\TicketPriority;
use App\Modules\TicketFlow\Enums\TicketStatus;
use App\Modules\TicketFlow\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can create a ticket', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/tickets', [
        'title' => 'Login sayfasindaki hata',
        'description' => 'Yanlis sifreden sonra hata mesaji gorunmuyor.',
        'status' => 'open',
        'priority' => 'high',
        'due_date' => '2026-08-01',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Login sayfasindaki hata')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.due_date', '2026-08-01');

    $this->assertDatabaseHas('tickets', [
        'title' => 'Login sayfasindaki hata',
        'user_id' => $user->id,
    ]);
});

test('user_id from the payload is ignored on create', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->postJson('/api/tickets', [
        'title' => 'Valid title',
        'description' => 'A perfectly valid description.',
        'status' => 'open',
        'priority' => 'low',
        'user_id' => $other->id,
    ])->assertCreated();

    $this->assertDatabaseHas('tickets', [
        'title' => 'Valid title',
        'user_id' => $user->id,
    ]);
});

test('ticket creation validates the payload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/tickets', [
        'title' => 'ab',
        'description' => 'short',
        'status' => 'invalid',
        'priority' => 'nope',
        'due_date' => '01-08-2026',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'status', 'priority', 'due_date']);
});

test('a user only lists their own tickets', function () {
    $user = User::factory()->create();
    Ticket::factory(3)->for($user)->create();
    Ticket::factory(2)->for(User::factory())->create();

    $this->actingAs($user)->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('a user can view their own ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create();

    $this->actingAs($user)->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id);
});

test('a user cannot view another users ticket (404)', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for(User::factory())->create();

    $this->actingAs($user)->getJson("/api/tickets/{$ticket->id}")->assertNotFound();
});

test('a user can update their own ticket (PATCH partial)', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->status(TicketStatus::Open)->create();

    $this->actingAs($user)->patchJson("/api/tickets/{$ticket->id}", [
        'status' => 'resolved',
    ])->assertOk()->assertJsonPath('data.status', 'resolved');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

test('a user cannot update another users ticket (404)', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for(User::factory())->create();

    $this->actingAs($user)->patchJson("/api/tickets/{$ticket->id}", [
        'status' => 'closed',
    ])->assertNotFound();
});

test('a user can delete their own ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/tickets/{$ticket->id}")->assertNoContent();

    $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
});

test('a user cannot delete another users ticket (404)', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for(User::factory())->create();

    $this->actingAs($user)->deleteJson("/api/tickets/{$ticket->id}")->assertNotFound();

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
});

test('search filters results without leaking other users records', function () {
    $user = User::factory()->create();

    Ticket::factory()->for($user)->create(['title' => 'Login bug', 'description' => 'irrelevant']);
    Ticket::factory()->for($user)->create(['title' => 'Signup issue', 'description' => 'irrelevant']);
    Ticket::factory()->for(User::factory())->create(['title' => 'Login broken', 'description' => 'other user']);

    $this->actingAs($user)->getJson('/api/tickets?search=login')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Login bug');
});

test('status and priority filters work', function () {
    $user = User::factory()->create();
    Ticket::factory()->for($user)->status(TicketStatus::Open)->priority(TicketPriority::High)->create();
    Ticket::factory()->for($user)->status(TicketStatus::Closed)->priority(TicketPriority::Low)->create();

    $this->actingAs($user)->getJson('/api/tickets?status=open')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'open');

    $this->actingAs($user)->getJson('/api/tickets?priority=low')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.priority', 'low');
});

test('sorting and pagination work', function () {
    $user = User::factory()->create();
    Ticket::factory()->for($user)->create(['title' => 'Ccc']);
    Ticket::factory()->for($user)->create(['title' => 'Aaa']);
    Ticket::factory()->for($user)->create(['title' => 'Bbb']);

    $this->actingAs($user)->getJson('/api/tickets?sort=title&direction=asc&per_page=5')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Aaa')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

test('invalid query parameters return 422', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/tickets?status=nope&sort=secret&direction=sideways&per_page=999')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status', 'sort', 'direction', 'per_page']);
});
