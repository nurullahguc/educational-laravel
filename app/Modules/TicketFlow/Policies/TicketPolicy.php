<?php

namespace App\Modules\TicketFlow\Policies;

use App\Models\User;
use App\Modules\TicketFlow\Models\Ticket;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $this->owns($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->owns($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->owns($user, $ticket);
    }

    private function owns(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }
}
