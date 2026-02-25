<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliveryRequest extends FormRequest
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
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'delivered_at' => ['nullable', 'date'],
        ];
    }

    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can complete this delivery');
    }
}
