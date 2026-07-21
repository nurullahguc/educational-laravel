<?php

namespace App\Modules\TicketFlow\Database\Seeders;

use App\Models\User;
use App\Modules\TicketFlow\Enums\TicketPriority;
use App\Modules\TicketFlow\Enums\TicketStatus;
use App\Modules\TicketFlow\Models\Ticket;
use Illuminate\Database\Seeder;

/**
 * Development-only demo data for TicketFlow. Credentials are documented in the
 * README and must never be used outside local development.
 */
class TicketFlowSeeder extends Seeder
{
    public function run(): void
    {
        $demoUsers = [
            ['name' => 'Nurullah Güç', 'email' => 'nurullah@example.com'],
            ['name' => 'Demo User', 'email' => 'demo@example.com'],
        ];

        foreach ($demoUsers as $attributes) {
            $user = User::firstOrCreate(
                ['email' => $attributes['email']],
                ['name' => $attributes['name'], 'password' => 'password123'],
            );

            // A spread of statuses and priorities so the frontend has variety.
            Ticket::factory()->for($user)->status(TicketStatus::Open)->priority(TicketPriority::High)->create();
            Ticket::factory()->for($user)->status(TicketStatus::InProgress)->priority(TicketPriority::Medium)->create();
            Ticket::factory()->for($user)->status(TicketStatus::Resolved)->priority(TicketPriority::Low)->create();
            Ticket::factory()->for($user)->status(TicketStatus::Closed)->priority(TicketPriority::Critical)->create();
            Ticket::factory(6)->for($user)->create();
        }
    }
}
