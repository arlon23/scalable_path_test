<?php

namespace Tests\Feature;

use App\Imports\ReviewsImport;
use App\Models\Review;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row as ExcelRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ReviewImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_the_public_csv_for_chunked_import(): void
    {
        Excel::fake();

        $this->artisan('app:import-reviews')->assertSuccessful();

        Excel::assertQueued(
            public_path('customer_reviews.csv'),
            fn (ReviewsImport $import): bool => $import instanceof ShouldQueue
                && $import instanceof WithChunkReading,
        );
    }

    public function test_import_normalizes_sources_and_updates_reviews_and_responses_idempotently(): void
    {
        $import = new ReviewsImport;

        $firstReview = $this->reviewRow('REV-1', 'Google', 'First review', 'Response one', '2026-07-03');
        $secondReview = $this->reviewRow('REV-2', 'google', 'Second review', 'Response two', null);
        $thirdReview = $this->reviewRow('REV-3', 'Google Reviews', 'Third review', null, null);

        $this->importRow($import, $firstReview);
        $this->importRow($import, $secondReview);
        $this->importRow($import, $thirdReview);
        $this->importRow($import, [
            ...$secondReview,
            'source' => 'Google Reviews',
            'review_text' => 'Updated review',
            'response' => 'Updated response',
        ]);

        $this->assertDatabaseCount('review_sources', 1);
        $this->assertDatabaseHas('review_sources', ['source' => 'Google']);
        $this->assertDatabaseCount('reviews', 3);
        $this->assertDatabaseCount('review_responses', 2);

        $respondedReview = Review::where('review_id', 'REV-1')->firstOrFail();
        $responseWithoutDate = Review::where('review_id', 'REV-2')->firstOrFail();
        $reviewWithoutResponse = Review::where('review_id', 'REV-3')->firstOrFail();

        $this->assertSame('2026-07-03', $respondedReview->response_date->toDateString());
        $this->assertSame('John Smith', $respondedReview->response->responded_by);
        $this->assertNull($responseWithoutDate->response_date);
        $this->assertSame('Updated review', $responseWithoutDate->review_text);
        $this->assertSame('Updated response', $responseWithoutDate->response->response);
        $this->assertNull($reviewWithoutResponse->response);
    }

    public function test_queued_import_reads_the_public_csv_and_keeps_response_date_as_a_separate_flag(): void
    {
        config(['queue.default' => 'sync']);

        $this->artisan('app:import-reviews')->assertSuccessful();

        $this->assertDatabaseCount('reviews', 20);
        $this->assertDatabaseCount('review_sources', 3);

        $review = Review::with(['source', 'response'])
            ->where('review_id', 'REV-10488')
            ->firstOrFail();

        $this->assertSame('Facebook', $review->source->source);
        $this->assertNull($review->response_date);
        $this->assertSame('Maria Garcia', $review->response->responded_by);
    }

    /**
     * @return array<string, string|null>
     */
    private function reviewRow(
        string $reviewId,
        string $source,
        string $reviewText,
        ?string $response,
        ?string $responseDate,
    ): array {
        return [
            'review_id' => $reviewId,
            'source' => $source,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'rating' => '5',
            'review_title' => 'Test review',
            'review_text' => $reviewText,
            'review_date' => '2026-07-02',
            'response' => $response,
            'response_date' => $responseDate,
            'responded_by' => $response === null ? null : 'John Smith',
        ];
    }

    /**
     * @param  array<string, string|null>  $attributes
     */
    private function importRow(ReviewsImport $import, array $attributes): void
    {
        $headings = array_keys($attributes);
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($headings, null, 'A1');
        $worksheet->fromArray(array_values($attributes), null, 'A2');

        $import->onRow(new ExcelRow(
            $worksheet->getRowIterator(2)->current(),
            $headings,
            array_fill(0, count($headings), false),
        ));

        $spreadsheet->disconnectWorksheets();
    }
}
