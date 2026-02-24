<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliveryRequest extends FormRequest
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
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'delivered_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom authorization message
     */
    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can complete this delivery');
    }
}
