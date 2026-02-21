<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\VehicleType;
use App\Shared\Enums\BusinessType;

class StoreLoadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and have an approved shipper business
        if (!$this->user() || !$this->user()->business) {
            return false;
        }

        return $this->user()->business->type === BusinessType::Shipper;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Pickup details
            'pickup_address' => ['required', 'string', 'max:255'],
            'pickup_city' => ['required', 'string', 'max:100'],
            'pickup_state' => ['required', 'string', 'max:100'],
            'pickup_zip' => ['required', 'string', 'max:20'],
            'pickup_country' => ['nullable', 'string', 'max:100'],
            
            // Delivery details
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:100'],
            'delivery_state' => ['required', 'string', 'max:100'],
            'delivery_zip' => ['required', 'string', 'max:20'],
            'delivery_country' => ['nullable', 'string', 'max:100'],
            
            // Dates
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_date' => ['required', 'date', 'after:pickup_date'],
            
            // Cargo details
            'cargo_type' => ['required', 'string', 'max:100'],
            'cargo_weight_kg' => ['required', 'numeric', 'min:1', 'max:100000'],
            'cargo_description' => ['nullable', 'string', 'max:1000'],
            
            // Requirements
            'vehicle_type_required' => ['required', 'string', 'in:' . implode(',', array_column(VehicleType::cases(), 'value'))],
            'price' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            
            // Optional fields
            'special_requirements' => ['nullable', 'string', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'status' => ['nullable', 'string', 'in:' . implode(',', [LoadStatus::Draft->value, LoadStatus::Open->value])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'pickup_date.after_or_equal' => 'Pickup date must be today or a future date',
            'delivery_date.after' => 'Delivery date must be after pickup date',
            'vehicle_type_required.in' => 'Invalid vehicle type selected',
            'status.in' => 'Status must be either draft or open',
        ];
    }

    /**
     * Get custom authorization message
     */
    public function failedAuthorization()
    {
        abort(403, 'Only shipper businesses can post loads');
    }
}
