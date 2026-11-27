<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\ReviewSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerReviewsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
    }

    public function test_homepage_shows_review_summary_and_paginated_reviews(): void
    {
        $source = ReviewSource::factory()->create(['source' => 'Google']);

        for ($index = 1; $index <= 11; $index++) {
            $review = Review::factory()
                ->for($source, 'source')
                ->create([
                    'review_id' => "REV-{$index}",
                    'rating' => $index === 1 ? 5 : 3,
                    'review_date' => "2026-07-{$index}",
                    'response_date' => $index === 1 ? '2026-07-15' : null,
                ]);

            if ($index === 1) {
                ReviewResponse::factory()->for($review)->create();
            }
        }

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reviews/Index')
                ->has('reviews.data', 10)
                ->where('reviews.total', 11)
                ->where('stats.total', 11)
                ->where('stats.responded', 1)
                ->where('stats.needs_response', 10)
                ->where('sources.0.source', 'Google')
                ->where('filters.status', 'all'));
    }

    #[DataProvider('searchableReviewFields')]
    public function test_search_finds_reviews_by_each_requested_field(string $field): void
    {
        $source = ReviewSource::factory()->create();
        $review = Review::factory()
            ->for($source, 'source')
            ->create([$field => 'DistinctiveSearchTerm']);

        $this->get('/?search=DistinctiveSearchTerm')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reviews/Index')
                ->has('reviews.data', 1)
                ->where('reviews.data.0.review_id', $review->review_id));
    }

    public static function searchableReviewFields(): array
    {
        return [
            'customer name' => ['customer_name'],
            'customer email' => ['customer_email'],
            'review title' => ['review_title'],
            'review text' => ['review_text'],
        ];
    }

    public function test_source_and_response_status_filters_use_response_or_response_date(): void
    {
        $google = ReviewSource::factory()->create(['source' => 'Google']);
        $facebook = ReviewSource::factory()->create(['source' => 'Facebook']);

        $pendingReview = Review::factory()
            ->for($google, 'source')
            ->create([
                'review_id' => 'PENDING-1',
                'review_date' => '2026-07-18',
                'response_date' => null,
            ]);
        $respondedReview = Review::factory()
            ->for($google, 'source')
            ->create([
                'review_id' => 'RESPONDED-1',
                'review_date' => '2026-07-20',
                'response_date' => '2026-07-20',
            ]);
        $responseWithoutDate = Review::factory()
            ->for($google, 'source')
            ->create([
                'review_id' => 'RESPONDED-2',
                'review_date' => '2026-07-19',
                'response_date' => null,
            ]);
        Review::factory()
            ->for($facebook, 'source')
            ->create([
                'review_id' => 'PENDING-2',
                'review_date' => '2026-07-17',
                'response_date' => null,
            ]);
        ReviewResponse::factory()->for($respondedReview)->create();
        ReviewResponse::factory()->for($responseWithoutDate)->create();

        $this->get('/?source_id='.$google->id.'&status=needs_response')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.review_id', 'PENDING-1')
                ->where('filters.status', 'needs_response'));

        $this->get('/?source_id='.$google->id.'&status=responded')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews.data', 2)
                ->where('reviews.data.0.review_id', 'RESPONDED-1')
                ->where('reviews.data.1.review_id', 'RESPONDED-2')
                ->where('reviews.data.1.response_date', null)
                ->where('stats.responded', 2)
                ->where('stats.needs_response', 2));
    }
}
