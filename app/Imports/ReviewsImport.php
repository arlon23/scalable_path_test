<?php

namespace App\Imports;

use App\Models\Review;
use App\Models\ReviewSource;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use UnexpectedValueException;

class ReviewsImport implements OnEachRow, ShouldQueue, WithChunkReading, WithHeadingRow
{
    /**
     * @throws UnexpectedValueException
     */
    public function onRow(Row $row): void
    {
        $attributes = $row->toArray();
        $reviewId = $this->nullableString($attributes['review_id'] ?? null);
        $source = $this->nullableString($attributes['source'] ?? null);

        if ($reviewId === null || $source === null) {
            throw new UnexpectedValueException(
                "Review row {$row->getIndex()} must include review_id and source.",
            );
        }

        $reviewSource = ReviewSource::firstOrCreate([
            'source' => $this->normalizeSource($source),
        ]);

        $review = Review::updateOrCreate(
            [
                'source_id' => $reviewSource->id,
                'review_id' => $reviewId,
            ],
            [
                'customer_name' => $this->nullableString($attributes['customer_name'] ?? null),
                'customer_email' => $this->nullableString($attributes['customer_email'] ?? null),
                'rating' => $this->nullableRating($attributes['rating'] ?? null, $row->getIndex()),
                'review_title' => $this->nullableString($attributes['review_title'] ?? null),
                'review_text' => $this->nullableString($attributes['review_text'] ?? null),
                'review_date' => $this->nullableDate($attributes['review_date'] ?? null),
                'response_date' => $this->nullableDate($attributes['response_date'] ?? null),
            ],
        );

        $response = $this->nullableString($attributes['response'] ?? null);

        if ($response === null) {
            $review->response()->delete();

            return;
        }

        $review->response()->updateOrCreate([], [
            'response' => $response,
            'responded_by' => $this->nullableString($attributes['responded_by'] ?? null),
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function normalizeSource(string $source): string
    {
        $source = Str::of($source)
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish()
            ->toString();
        $sourceKey = Str::lower(Str::replace(' ', '', $source));

        return match ($sourceKey) {
            'google', 'googlereviews' => 'Google',
            'tripadvisor' => 'Tripadvisor',
            'facebook' => 'Facebook',
            default => Str::title($source),
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new UnexpectedValueException('Review CSV values must be scalar.');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableRating(mixed $value, int $rowIndex): ?string
    {
        $rating = $this->nullableString($value);

        if ($rating !== null && ! is_numeric($rating)) {
            throw new UnexpectedValueException("Review row {$rowIndex} has an invalid rating.");
        }

        return $rating;
    }

    private function nullableDate(mixed $value): ?string
    {
        $date = $this->nullableString($value);

        return $date === null ? null : Carbon::parse($date)->toDateTimeString();
    }
}
