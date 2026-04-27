<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Book</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.books.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="title" value="Title" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="author" value="Author" />
                        <x-text-input id="author" name="author" type="text" class="mt-1 block w-full" :value="old('author')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('author')" />
                    </div>

                    <div>
                        <x-input-label for="published_year" value="Published Year" />
                        <x-text-input id="published_year" name="published_year" type="number" class="mt-1 block w-full" :value="old('published_year')" />
                        <x-input-error class="mt-2" :messages="$errors->get('published_year')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Create</x-primary-button>
                        <a href="{{ route('admin.books.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
