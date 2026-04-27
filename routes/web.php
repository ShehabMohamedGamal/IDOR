<?php

use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\IndirectReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UuidReviewController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/books');

Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('books.index'))
        ->middleware('verified')
        ->name('dashboard');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/indirect/reviews/{reference}', [IndirectReviewController::class, 'edit'])->name('reviews.indirect.edit');
    Route::put('/indirect/reviews/{reference}', [IndirectReviewController::class, 'update'])->name('reviews.indirect.update');
    Route::get('/uuid/reviews/{uuid}', [UuidReviewController::class, 'edit'])->name('reviews.uuid.edit');
    Route::put('/uuid/reviews/{uuid}', [UuidReviewController::class, 'update'])->name('reviews.uuid.update');

    Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserProfileController::class, 'update'])->name('users.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('books', AdminBookController::class)->except(['show']);
    });

require __DIR__.'/auth.php';
