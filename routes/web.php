<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
    // Books
    Route::controller(BookController::class)->group(function () {
        Route::get('/books/create', 'create')->name('books.create');
        Route::post('/books/create', 'store')->name('books.store');
        Route::get('/books/isbn/{isbn}', 'fetchByIsbn');
        Route::get('/books/{book}/edit', 'edit')->name('books.edit');
        Route::put('/books/{book}/edit', 'update')->name('books.update');
        Route::delete('/books/{book}', 'destroy')->name('books.destroy');
    });

    // Reviews
    Route::controller(ReviewController::class)->group(function () {
        Route::post('/books/{book}/reviews', 'store')->name('reviews.store');
        Route::post('/reviews/{review}/like', 'like')->name('reviews.like');
        Route::get('/reviews/{review}/edit', 'edit')->name('reviews.edit');
        Route::put('/reviews/{review}', 'update')->name('reviews.update');
        Route::delete('/reviews/{review}', 'destroy')->name('reviews.destroy');
    });

    // Favorites
    Route::controller(FavoriteController::class)->group(function () {
        Route::get('/favorites', 'index')->name('favorites.index');
        Route::post('/books/{book}/favorite', 'toggle')->name('favorites.toggle');
    });

    // Genres
    Route::resource('genres', GenreController::class)->except(['show']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Reading Plans
    Route::controller(ReadingPlanController::class)->group(function () {
        Route::get('/reading-plans', 'index')->name('reading-plans.index');
        Route::get('/reading-plans/create', 'create')->name('reading-plans.create');
        Route::post('/reading-plans/create', 'store')->name('reading-plans.store');
        Route::post('/reading-plans/{plan}/complete', 'complete')->name('reading-plans.complete');
        Route::get('/reading-plans/{plan}/edit', 'edit')->name('reading-plans.edit');
        Route::put('/reading-plans/{plan}/edit', 'update')->name('reading-plans.update');
        Route::delete('/reading-plans/{plan}', 'destroy')->name('reading-plans.destroy');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

// Public
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');