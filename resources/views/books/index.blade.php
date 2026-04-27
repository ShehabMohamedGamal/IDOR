<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Books</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4">
                @forelse ($books as $book)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $book->title }}</h3>
                                <p class="text-sm text-gray-600 mt-1">by {{ $book->author }}</p>
                                @if ($book->published_year)
                                    <p class="text-xs text-gray-500 mt-1">Published {{ $book->published_year }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-700">{{ $book->reviews_count }} reviews</p>
                                <p class="text-sm text-gray-700">Avg {{ number_format((float) ($book->reviews_avg_rating ?? 0), 1) }}/5</p>
                                <a href="{{ route('books.show', $book) }}" class="inline-block mt-3 text-sm text-indigo-600 hover:text-indigo-800 underline">View details</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-600">
                        No books yet. @auth Ask admin to add books. @else Login to start reviewing. @endauth
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
