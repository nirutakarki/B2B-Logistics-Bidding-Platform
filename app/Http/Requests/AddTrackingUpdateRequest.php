<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTrackingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $load = $this->route('load');
        
        // User must be the assigned driver
        return $this->user() && 
               $this->user()->business && 
               $load->assigned_driver_id === $this->user()->business->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please provide a status update message',
            'message.max' => 'Update message cannot exceed 500 characters',
        ];
    }

    /**
     * Get custom authorization message
     */
    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can add tracking updates');
    }
}
