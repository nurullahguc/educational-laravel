<?php

use App\Modules\TicketFlow\TicketFlowServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Project modules
    TicketFlowServiceProvider::class,
];
