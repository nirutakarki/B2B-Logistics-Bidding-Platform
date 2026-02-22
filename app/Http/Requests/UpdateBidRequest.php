<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBidRequest extends FormRequest
{

    public function authorize(): bool
    {
        $bid = $this->route('bid');
        
        return $this->user() && 
               $this->user()->business && 
               $bid->driver_business_id === $this->user()->business->id;
    }


    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'min:1', 'max:999999.99'],
            'vehicle_id' => ['sometimes', 'nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Bid amount must be at least $1',
            'amount.max' => 'Bid amount cannot exceed $999,999.99',
            'vehicle_id.exists' => 'Selected vehicle does not exist',
        ];
    }


    public function failedAuthorization()
    {
        abort(403, 'You can only update your own bids');
    }
}
