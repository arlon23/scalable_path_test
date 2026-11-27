<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'review_id',
    'source_id',
    'customer_name',
    'customer_email',
    'rating',
    'review_title',
    'review_text',
    'review_date',
    'response_date',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public function source(): BelongsTo
    {
        return $this->belongsTo(ReviewSource::class, 'source_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class);
    }

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'review_date' => 'datetime',
            'response_date' => 'datetime',
        ];
    }
}
