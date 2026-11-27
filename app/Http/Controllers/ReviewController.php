<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewSource;
use App\Services\ReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer', Rule::exists('review_sources', 'id')],
            'status' => ['nullable', Rule::in(['responded', 'needs_response'])],
        ]);
        $search = trim($filters['search'] ?? '');

        $reviews = Review::query()
            ->select([
                'id',
                'review_id',
                'source_id',
                'customer_name',
                'customer_email',
                'rating',
                'review_title',
                'review_text',
                'review_date',
                'response_date',
            ])
            ->with([
                'source:id,source',
                'response:id,review_id,response,responded_by',
            ])
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->when(
                isset($filters['source_id']),
                fn (Builder $query) => $query->where('source_id', $filters['source_id']),
            )
            ->when(
                ($filters['status'] ?? null) === 'responded',
                fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->whereNotNull('response_date')
                        ->orWhereHas('response');
                }),
            )
            ->when(
                ($filters['status'] ?? null) === 'needs_response',
                fn (Builder $query) => $query->whereNull('response_date')
                    ->whereDoesntHave('response'),
            )
            ->latest('review_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $summary = Review::query()
            ->leftJoin('review_responses', 'review_responses.review_id', '=', 'reviews.id')
            ->selectRaw(
                'COUNT(reviews.id) AS total,
                COALESCE(AVG(reviews.rating), 0) AS average_rating,
                SUM(CASE WHEN reviews.response_date IS NOT NULL OR review_responses.id IS NOT NULL THEN 1 ELSE 0 END) AS responded,
                SUM(CASE WHEN reviews.response_date IS NULL AND review_responses.id IS NULL THEN 1 ELSE 0 END) AS needs_response',
            )
            ->first();

        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews,
            'sources' => ReviewSource::query()
                ->whereHas('reviews')
                ->orderBy('source')
                ->get(['id', 'source']),
            'filters' => [
                'search' => $search,
                'source_id' => (string) ($filters['source_id'] ?? ''),
                'status' => $filters['status'] ?? 'all',
            ],
            'stats' => [
                'total' => (int) $summary->total,
                'average_rating' => (float) $summary->average_rating,
                'responded' => (int) $summary->responded,
                'needs_response' => (int) $summary->needs_response,
            ],
        ]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $columns = ['customer_name', 'customer_email', 'review_title', 'review_text'];

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'], true)) {
            $query->whereFullText($columns, $search);

            return;
        }

        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }
}
