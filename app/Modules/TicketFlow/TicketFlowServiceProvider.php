<?php

namespace App\Modules\TicketFlow;

use App\Modules\TicketFlow\Models\Ticket;
use App\Modules\TicketFlow\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the self-contained TicketFlow module into the app: its migrations,
 * routes and authorization policy. Every new project gets a sibling provider
 * like this one, registered in bootstrap/providers.php.
 */
class TicketFlowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Gate::policy(Ticket::class, TicketPolicy::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/Routes/api.php');
    }
}
