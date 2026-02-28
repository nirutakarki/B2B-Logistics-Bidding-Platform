<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Shared\Enums\LoadStatus;

class CreateRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $load = $this->route('load');
        $business = $this->user()->business;
        
        if ($load->status !== LoadStatus::Completed) {
            return false;
        }
        
        // User must be either the shipper or the assigned driver
        $isShipper = $load->business_id === $business->id;
        $isDriver = $load->assigned_driver_id === $business->id;
        
        return $isShipper || $isDriver;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.min' => 'Rating must be between 1 and 5 stars',
            'rating.max' => 'Rating must be between 1 and 5 stars',
        ];
    }
}
