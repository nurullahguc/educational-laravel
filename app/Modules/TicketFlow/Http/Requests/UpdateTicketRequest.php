<?php

namespace App\Modules\TicketFlow\Http\Requests;

use App\Modules\TicketFlow\Enums\TicketPriority;
use App\Modules\TicketFlow\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles both PUT (full) and PATCH (partial) updates. Using `sometimes` means
 * a field is only validated when it is actually present in the payload, which
 * is exactly what PATCH needs while still validating everything PUT sends.
 */
class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:150'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'status' => ['sometimes', 'required', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(TicketPriority::class)],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
