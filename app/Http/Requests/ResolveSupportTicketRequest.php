<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveSupportTicketRequest extends FormRequest
{

    public function authorize(): bool
    {
        // Only platform admins can resolve tickets
        return $this->user()->hasRole('platform_admin');
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
