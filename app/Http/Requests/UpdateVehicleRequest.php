<?php

namespace App\Http\Requests;

use App\Shared\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{

    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');
        
        return $this->user() 
            && $this->user()->business 
            && $vehicle->business_id === $this->user()->business_id;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')->id;
        
        return [
            'registration_number' => ['sometimes', 'string', 'max:50', 'unique:vehicles,registration_number,' . $vehicleId],
            'vehicle_type' => ['sometimes', 'in:' . implode(',', array_column(VehicleType::cases(), 'value'))],
            'capacity' => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
        ];
    }
}

