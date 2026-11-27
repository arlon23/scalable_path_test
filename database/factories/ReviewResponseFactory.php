<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewResponse>
 */
class ReviewResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'response' => fake()->paragraph(),
            'responded_by' => fake()->name(),
        ];
    }
}
