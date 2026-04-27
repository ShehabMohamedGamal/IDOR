<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Review (Indirect Reference)</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600">Book: {{ $review->book->title }}</p>
                <p class="text-xs text-gray-500 mt-1">Current reference used: <code>{{ $reference }}</code></p>
                <p class="text-xs text-gray-500 mt-1">Sample refs:
                    <code>{{ $referenceSamples['filename'] }}</code>,
                    <code>{{ $referenceSamples['hash'] }}</code>,
                    <code>{{ $referenceSamples['encoded'] }}</code>
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('reviews.indirect.update', $reference) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="rating" value="Rating (1-5)" />
                        <select id="rating" name="rating" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" @selected((int) old('rating', $review->rating) === $i)>{{ $i }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="comment" value="Comment" />
                        <textarea id="comment" name="comment" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('comment', $review->comment) }}</textarea>
                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Save</x-primary-button>
                        <a href="{{ route('books.show', $review->book) }}" class="text-sm text-gray-600 underline">Back to book</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
