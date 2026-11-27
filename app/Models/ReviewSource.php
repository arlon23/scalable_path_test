<?php

namespace App\Models;

use Database\Factories\ReviewSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['source'])]
class ReviewSource extends Model
{
    /** @use HasFactory<ReviewSourceFactory> */
    use HasFactory;

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'source_id');
    }
}
