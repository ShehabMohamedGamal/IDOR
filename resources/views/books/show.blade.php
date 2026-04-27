<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $book->title }}</h2>
    </x-slot>

    <div class="py-8 space-y-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700">Author: {{ $book->author }}</p>
                @if ($book->published_year)
                    <p class="text-sm text-gray-700 mt-1">Published: {{ $book->published_year }}</p>
                @endif
            </div>
        </div>

        @auth
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900">{{ $userReview ? 'Update your review' : 'Add your review' }}</h3>

                    <form method="POST" action="{{ route('reviews.store', $book) }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="rating" value="Rating (1-5)" />
                            <select id="rating" name="rating" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected((int) old('rating', $userReview?->rating) === $i)>{{ $i }}</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="comment" value="Comment" />
                            <textarea id="comment" name="comment" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('comment', $userReview?->comment) }}</textarea>
                            <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ $userReview ? 'Save Review' : 'Post Review' }}</x-primary-button>
                    </form>
                </div>
            </div>
        @endauth

        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900">Reviews</h3>

                <div class="mt-4 space-y-4">
                    @forelse ($book->reviews as $review)
                        <div class="border border-gray-200 rounded-md p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-900">{{ $review->user->name }}</p>
                                <p class="text-sm text-gray-600">Rating: {{ $review->rating }}/5</p>
                            </div>
                            <p class="text-gray-700 mt-2">{{ $review->comment }}</p>
                            @php($refs = \App\Support\IndirectReviewReference::samples($review->id))
                            <p class="text-xs text-gray-500 mt-2">
                                Ref samples:
                                <code>{{ $refs['filename'] }}</code>,
                                <code>{{ $refs['hash'] }}</code>,
                                <code>{{ $refs['encoded'] }}</code>,
                                <code>{{ $refs['uuid'] }}</code>
                            </p>

                            @auth
                                @if (auth()->id() === $review->user_id)
                                    <div class="flex items-center gap-4 mt-3">
                                        <a href="{{ route('reviews.edit', $review) }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">Edit</a>
                                        <a href="{{ route('reviews.indirect.edit', $refs['encoded']) }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">Edit via ref</a>
                                        <a href="{{ route('reviews.uuid.edit', $refs['uuid']) }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">Edit via UUID</a>

                                        <form method="POST" action="{{ route('reviews.destroy', $review) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-red-600 hover:text-red-800 underline" type="submit">Delete</button>
                                        </form>
                                    </div>
                                @endif
                            @endauth
                        </div>
                    @empty
                        <p class="text-gray-600">No reviews yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
