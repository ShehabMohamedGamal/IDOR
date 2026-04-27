<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Support\IdorScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $effectiveUserId = $request->user()->id;

        // Vulnerable scenario: trust hidden `user_id` from body/JSON payload.
        if (IdorScenario::allowHiddenReviewUserIdParameter()) {
            $candidate = $request->input('user_id');

            if (is_numeric($candidate)) {
                $effectiveUserId = (int) $candidate;
            }
        }

        $review = Review::query()->updateOrCreate(
            [
                'book_id' => $book->id,
                'user_id' => $effectiveUserId,
            ],
            $validated,
        );

        return redirect()
            ->route('books.show', $book)
            ->with('status', $review->wasRecentlyCreated ? 'Review added.' : 'Review updated.');
    }

    public function edit(Review $review): View
    {
        if (! IdorScenario::bypassReviewOwnership()) {
            $this->authorize('update', $review);
        }

        return view('reviews.edit', [
            'review' => $review->load('book'),
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        if (! IdorScenario::bypassReviewOwnership()) {
            $this->authorize('update', $review);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $review->update($validated);

        return redirect()
            ->route('books.show', $review->book)
            ->with('status', 'Review saved.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        if (! IdorScenario::bypassReviewOwnership()) {
            $this->authorize('delete', $review);
        }

        $book = $review->book;
        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('status', 'Review deleted.');
    }
}
