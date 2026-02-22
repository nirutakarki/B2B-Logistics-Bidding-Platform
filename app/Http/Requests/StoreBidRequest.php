<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\BusinessType;

class StoreBidRequest extends FormRequest
{

    public function authorize(): bool
    {
        if (!$this->user() || !$this->user()->business) {
            return false;
        }

        return $this->user()->business->type === BusinessType::Driver;
    }


    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Bid amount is required',
            'amount.min' => 'Bid amount must be at least $1',
            'amount.max' => 'Bid amount cannot exceed $999,999.99',
            'vehicle_id.exists' => 'Selected vehicle does not exist',
        ];
    }

    public function failedAuthorization()
    {
        abort(403, 'Only driver businesses can place bids');
    }
}
