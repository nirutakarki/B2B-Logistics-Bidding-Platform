<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\VehicleType;
use App\Shared\Enums\BusinessType;

class StoreLoadRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user() || !$this->user()->business) {
            return false;
        }

        return $this->user()->business->type === BusinessType::Shipper;
    }

    public function rules(): array
    {
        return [
            'pickup_address' => ['required', 'string', 'max:255'],
            'pickup_city' => ['required', 'string', 'max:100'],
            'pickup_state' => ['required', 'string', 'max:100'],
            'pickup_zip' => ['required', 'string', 'max:20'],
            'pickup_country' => ['nullable', 'string', 'max:100'],
            
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:100'],
            'delivery_state' => ['required', 'string', 'max:100'],
            'delivery_zip' => ['required', 'string', 'max:20'],
            'delivery_country' => ['nullable', 'string', 'max:100'],
            
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_date' => ['required', 'date', 'after:pickup_date'],
            
            'cargo_type' => ['required', 'string', 'max:100'],
            'cargo_weight_kg' => ['required', 'numeric', 'min:1', 'max:100000'],
            'cargo_description' => ['nullable', 'string', 'max:1000'],
            
            'vehicle_type_required' => ['required', 'string', 'in:' . implode(',', array_column(VehicleType::cases(), 'value'))],
            'price' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            
            'special_requirements' => ['nullable', 'string', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'status' => ['nullable', 'string', 'in:' . implode(',', [LoadStatus::Draft->value, LoadStatus::Open->value])],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_date.after_or_equal' => 'Pickup date must be today or a future date',
            'delivery_date.after' => 'Delivery date must be after pickup date',
            'vehicle_type_required.in' => 'Invalid vehicle type selected',
            'status.in' => 'Status must be either draft or open',
        ];
    }

    public function failedAuthorization()
    {
        abort(403, 'Only shipper businesses can post loads');
    }
}
