<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\BusinessType;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and have an approved driver business
        if (!$this->user() || !$this->user()->business) {
            return false;
        }

        return $this->user()->business->type === BusinessType::Driver;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Bid amount is required',
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
        abort(403, 'Only driver businesses can place bids');
    }
}
