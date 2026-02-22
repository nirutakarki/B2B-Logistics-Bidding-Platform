<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $bid = $this->route('bid');
        
        // User must own the bid
        return $this->user() && 
               $this->user()->business && 
               $bid->driver_business_id === $this->user()->business->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'min:1', 'max:999999.99'],
            'vehicle_id' => ['sometimes', 'nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Bid amount must be at least $1',
            'amount.max' => 'Bid amount cannot exceed $999,999.99',
            'vehicle_id.exists' => 'Selected vehicle does not exist',
        ];
    }

    /**
     * Get custom authorization message
     */
    public function failedAuthorization()
    {
        abort(403, 'You can only update your own bids');
    }
}
