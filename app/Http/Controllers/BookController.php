<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('books.index', [
            'books' => Book::query()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Book $book): View
    {
        $book->load(['reviews' => function ($query) {
            $query->with('user')->latest();
        }]);

        return view('books.show', [
            'book' => $book,
            'userReview' => auth()->check()
                ? $book->reviews->firstWhere('user_id', auth()->id())
                : null,
        ]);
    }
}
