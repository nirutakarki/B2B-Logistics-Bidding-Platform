<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'rated_by_business_id',
        'rated_business_id',
        'load_id',
        'rating',
        'review_text',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * The business that gave the rating
     */
    public function ratedBy()
    {
        return $this->belongsTo(Business::class, 'rated_by_business_id');
    }

    /**
     * The business that received the rating
     */
    public function ratedBusiness()
    {
        return $this->belongsTo(Business::class, 'rated_business_id');
    }

    /**
     * The load this rating is for
     */
    public function shipment()
    {
        return $this->belongsTo(Load::class, 'load_id');
    }
}
