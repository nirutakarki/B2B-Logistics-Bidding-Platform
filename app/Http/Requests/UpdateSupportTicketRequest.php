<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User can update their own ticket
        $ticket = $this->route('ticket');
        return $ticket && $ticket->raised_by_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:2000'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => 'Priority must be one of: low, medium, high, urgent',
        ];
    }
}

