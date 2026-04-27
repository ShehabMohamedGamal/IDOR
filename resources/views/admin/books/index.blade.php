<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Books</h2>
            <a href="{{ route('admin.books.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">Add book</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="space-y-4">
                    @forelse ($books as $book)
                        <div class="flex items-center justify-between border border-gray-200 rounded-md p-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $book->title }}</p>
                                <p class="text-sm text-gray-600">{{ $book->author }} @if($book->published_year) ({{ $book->published_year }}) @endif</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <a href="{{ route('admin.books.edit', $book) }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">Edit</a>
                                <form method="POST" action="{{ route('admin.books.destroy', $book) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 underline">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600">No books found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
