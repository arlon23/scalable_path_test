<?php

namespace Tests\Feature\Models;

use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\ReviewSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_exposes_its_source_response_and_response_date(): void
    {
        $source = ReviewSource::factory()->create(['source' => 'Google']);
        $review = Review::factory()
            ->for($source, 'source')
            ->create([
                'review_id' => 'google-review-123',
                'response_date' => '2026-11-25 10:30:00',
            ]);
        $response = ReviewResponse::factory()
            ->for($review)
            ->create(['responded_by' => 'Support Agent']);

        $review->load(['source', 'response']);

        $this->assertSame('Google', $review->source->source);
        $this->assertSame($review->getKey(), $review->response->review->getKey());
        $this->assertTrue($source->reviews->contains('id', $review->getKey()));
        $this->assertSame($response->getKey(), $review->response->getKey());
        $this->assertInstanceOf(Carbon::class, $review->response_date);
    }
}
