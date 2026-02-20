<?php

namespace App\Http\Requests;

use App\Shared\Enums\BusinessType;
use App\Shared\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() 
            && $this->user()->business 
            && $this->user()->business->type === BusinessType::Driver;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'vehicle_type' => ['required', 'in:' . implode(',', array_column(VehicleType::cases(), 'value'))],
            'capacity' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_number.unique' => 'This vehicle registration number is already registered.',
            'capacity.min' => 'Vehicle capacity must be at least 0.1 tons.',
            'capacity.max' => 'Vehicle capacity cannot exceed 100 tons.',
        ];
    }
}
