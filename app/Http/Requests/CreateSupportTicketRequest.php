<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\SupportTicketPriority;

class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => 'Priority must be one of: low, medium, high, urgent',
        ];
    }
}
