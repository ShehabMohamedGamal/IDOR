<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\IdorScenario;
use App\Support\IndirectReviewReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UuidReviewController extends Controller
{
    public function edit(string $uuid): View
    {
        $review = $this->resolveReviewOrFail($uuid)->load('book');

        if (! IdorScenario::bypassUuidReviewOwnership()) {
            $this->authorize('update', $review);
        }

        return view('reviews.edit-uuid', [
            'review' => $review,
            'uuid' => $uuid,
            'referenceSamples' => IndirectReviewReference::samples($review->id),
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $review = $this->resolveReviewOrFail($uuid);

        if (! IdorScenario::bypassUuidReviewOwnership()) {
            $this->authorize('update', $review);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $review->update($validated);

        return redirect()
            ->route('books.show', $review->book)
            ->with('status', 'Review saved via UUID reference.');
    }

    private function resolveReviewOrFail(string $uuid): Review
    {
        $review = IndirectReviewReference::resolveUuid($uuid);

        abort_if($review === null, 404);

        return $review;
    }
}
