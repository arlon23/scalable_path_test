<?php

namespace App\Models;

use Database\Factories\ReviewResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['review_id', 'response', 'responded_by'])]
class ReviewResponse extends Model
{
    /** @use HasFactory<ReviewResponseFactory> */
    use HasFactory;

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
