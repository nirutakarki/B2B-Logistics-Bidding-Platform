<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartShipmentRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom authorization message
     */
    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can start this shipment');
    }
}
