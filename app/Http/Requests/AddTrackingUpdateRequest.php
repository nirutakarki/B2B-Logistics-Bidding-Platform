<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTrackingUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        $load = $this->route('load');
        
        return $this->user() && 
               $this->user()->business && 
               $load->assigned_driver_id === $this->user()->business->id;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Please provide a status update message',
            'message.max' => 'Update message cannot exceed 500 characters',
        ];
    }

    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can add tracking updates');
    }
}
