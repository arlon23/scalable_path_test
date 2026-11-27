<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => ReviewSource::factory(),
            'review_id' => fake()->uuid(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'rating' => fake()->randomFloat(2, 1, 5),
            'review_title' => fake()->sentence(),
            'review_text' => fake()->paragraph(),
            'review_date' => fake()->dateTimeBetween('-1 year'),
            'response_date' => null,
        ];
    }
}
