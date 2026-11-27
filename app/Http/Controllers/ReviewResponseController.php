<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewResponseRequest;
use App\Http\Requests\UpdateReviewResponseRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReviewResponseController extends Controller
{
    public function store(StoreReviewResponseRequest $request, Review $review): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($review, $validated): void {
            $review->response()->create($validated);
            $this->setResponseDateIfMissing($review);
        });

        return back();
    }

    public function update(UpdateReviewResponseRequest $request, Review $review): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($review, $validated): void {
            $review->response()->firstOrFail()->update($validated);
            $this->setResponseDateIfMissing($review);
        });

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->response()->firstOrFail()->delete();

        return back();
    }

    private function setResponseDateIfMissing(Review $review): void
    {
        if ($review->response_date === null) {
            $review->update(['response_date' => now()]);
        }
    }
}
