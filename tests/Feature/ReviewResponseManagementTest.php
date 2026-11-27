<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewResponseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
    }

    public function test_store_response_sets_the_response_date_to_the_current_time(): void
    {
        $review = Review::factory()->create(['response_date' => null]);
        $this->travelTo(Carbon::parse('2026-11-26 18:00:00'));

        $this->from('/')
            ->post(route('reviews.responses.store', $review), [
                'response' => 'Thank you for sharing your feedback.',
                'responded_by' => 'Support Agent',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('review_responses', [
            'review_id' => $review->id,
            'response' => 'Thank you for sharing your feedback.',
            'responded_by' => 'Support Agent',
        ]);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'response_date' => '2026-11-26 18:00:00',
        ]);

        $this->get('/?status=responded')
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews.data', 1)
                ->where('stats.responded', 1)
                ->where('stats.needs_response', 0));
    }

    public function test_store_response_requires_response_text(): void
    {
        $review = Review::factory()->create();

        $this->from('/')
            ->post(route('reviews.responses.store', $review), [
                'responded_by' => 'Support Agent',
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('response');

        $this->assertDatabaseCount('review_responses', 0);
    }

    public function test_update_response_sets_a_missing_response_date_to_the_current_time(): void
    {
        $review = Review::factory()->create(['response_date' => null]);
        $this->travelTo(Carbon::parse('2026-11-26 18:00:00'));
        $response = ReviewResponse::factory()
            ->for($review)
            ->create([
                'response' => 'Original response',
                'responded_by' => 'Previous Agent',
            ]);

        $this->from('/')
            ->put(route('reviews.responses.update', $review), [
                'response' => 'Updated response',
                'responded_by' => 'Current Agent',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'response' => 'Updated response',
            'responded_by' => 'Current Agent',
        ]);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'response_date' => '2026-11-26 18:00:00',
        ]);
    }

    public function test_update_response_preserves_an_existing_response_date(): void
    {
        $review = Review::factory()->create(['response_date' => '2026-07-14']);
        $response = ReviewResponse::factory()->for($review)->create();
        $this->travelTo(Carbon::parse('2026-11-26 18:00:00'));

        $this->from('/')
            ->put(route('reviews.responses.update', $review), [
                'response' => 'Updated response',
                'responded_by' => 'Current Agent',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'response_date' => '2026-07-14 00:00:00',
        ]);
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'response' => 'Updated response',
        ]);
    }

    public function test_delete_response_preserves_the_existing_response_date(): void
    {
        $review = Review::factory()->create(['response_date' => '2026-07-14']);
        $response = ReviewResponse::factory()->for($review)->create();

        $this->from('/')
            ->delete(route('reviews.responses.destroy', $review))
            ->assertRedirect('/');

        $this->assertDatabaseMissing('review_responses', ['id' => $response->id]);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'response_date' => '2026-07-14 00:00:00',
        ]);
    }
}
