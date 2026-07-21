<?php

namespace Database\Seeders;

use App\Modules\TicketFlow\Database\Seeders\TicketFlowSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Each project module ships its own seeder; register them here.
     */
    public function run(): void
    {
        $this->call([
            TicketFlowSeeder::class,
        ]);
    }
}
