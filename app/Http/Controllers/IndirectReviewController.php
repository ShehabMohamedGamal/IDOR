<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\IdorScenario;
use App\Support\IndirectReviewReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndirectReviewController extends Controller
{
    public function edit(string $reference): View
    {
        $review = $this->resolveReviewOrFail($reference)->load('book');

        if (! IdorScenario::bypassIndirectReviewOwnership()) {
            $this->authorize('update', $review);
        }

        return view('reviews.edit-indirect', [
            'review' => $review,
            'reference' => $reference,
            'referenceSamples' => IndirectReviewReference::samples($review->id),
        ]);
    }

    public function update(Request $request, string $reference): RedirectResponse
    {
        $review = $this->resolveReviewOrFail($reference);

        if (! IdorScenario::bypassIndirectReviewOwnership()) {
            $this->authorize('update', $review);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $review->update($validated);

        return redirect()
            ->route('books.show', $review->book)
            ->with('status', 'Review saved via indirect reference.');
    }

    private function resolveReviewOrFail(string $reference): Review
    {
        $review = IndirectReviewReference::resolve($reference);

        abort_if($review === null, 404);

        return $review;
    }
}
