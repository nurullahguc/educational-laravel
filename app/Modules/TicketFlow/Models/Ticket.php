<?php

namespace App\Modules\TicketFlow\Models;

use App\Models\User;
use App\Modules\TicketFlow\Database\Factories\TicketFactory;
use App\Modules\TicketFlow\Enums\TicketPriority;
use App\Modules\TicketFlow\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * user_id is intentionally excluded: ownership is always assigned from the
     * authenticated user, never from client input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The factory lives inside the module, so point the trait at it explicitly.
     */
    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}
