<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\LoadStatus;
use App\Shared\Enums\VehicleType;

class UpdateLoadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $load = $this->route('load');
        
        // User must own the load
        return $this->user() && 
               $this->user()->business && 
               $load->business_id === $this->user()->business->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Pickup details (all optional for updates)
            'pickup_address' => ['sometimes', 'string', 'max:255'],
            'pickup_city' => ['sometimes', 'string', 'max:100'],
            'pickup_state' => ['sometimes', 'string', 'max:100'],
            'pickup_zip' => ['sometimes', 'string', 'max:20'],
            'pickup_country' => ['sometimes', 'string', 'max:100'],
            
            // Delivery details
            'delivery_address' => ['sometimes', 'string', 'max:255'],
            'delivery_city' => ['sometimes', 'string', 'max:100'],
            'delivery_state' => ['sometimes', 'string', 'max:100'],
            'delivery_zip' => ['sometimes', 'string', 'max:20'],
            'delivery_country' => ['sometimes', 'string', 'max:100'],
            
            // Dates
            'pickup_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'delivery_date' => ['sometimes', 'date', 'after:pickup_date'],
            
            // Cargo details
            'cargo_type' => ['sometimes', 'string', 'max:100'],
            'cargo_weight_kg' => ['sometimes', 'numeric', 'min:1', 'max:100000'],
            'cargo_description' => ['nullable', 'string', 'max:1000'],
            
            // Requirements
            'vehicle_type_required' => ['sometimes', 'string', 'in:' . implode(',', array_column(VehicleType::cases(), 'value'))],
            'price' => ['sometimes', 'numeric', 'min:1', 'max:999999.99'],
            
            // Optional fields
            'special_requirements' => ['nullable', 'string', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', [LoadStatus::Draft->value, LoadStatus::Open->value])],
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
        abort(403, 'You can only update your own loads');
    }
}
