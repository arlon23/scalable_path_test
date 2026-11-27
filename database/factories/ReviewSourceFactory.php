<?php

namespace Database\Factories;

use App\Models\ReviewSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewSource>
 */
class ReviewSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => fake()->unique()->word(),
        ];
    }
}
