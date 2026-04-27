<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Support\IdorScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.books.index', [
            'books' => Book::query()->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorizeUnlessTraining('create', Book::class);

        return view('admin.books.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeUnlessTraining('create', Book::class);

        Book::query()->create($this->validatedData($request));

        return redirect()
            ->route('admin.books.index')
            ->with('status', 'Book created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): View
    {
        $this->authorizeUnlessTraining('update', $book);

        return view('admin.books.edit', [
            'book' => $book,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book): RedirectResponse
    {
        $this->authorizeUnlessTraining('update', $book);

        $book->update($this->validatedData($request));

        return redirect()
            ->route('admin.books.index')
            ->with('status', 'Book updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorizeUnlessTraining('delete', $book);

        $book->delete();

        return redirect()
            ->route('admin.books.index')
            ->with('status', 'Book deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'published_year' => [
                'nullable',
                'integer',
                'min:1450',
                'max:'.date('Y'),
            ],
        ]);
    }

    private function authorizeUnlessTraining(string $ability, Book|string $book): void
    {
        if (IdorScenario::bypassBookAuthorization()) {
            return;
        }

        $this->authorize($ability, $book);
    }
}
