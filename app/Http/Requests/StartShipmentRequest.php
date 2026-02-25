<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartShipmentRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function failedAuthorization()
    {
        abort(403, 'Only the assigned driver can start this shipment');
    }
}
