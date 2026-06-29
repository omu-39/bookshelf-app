<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewRequest $request, Book $book)
    {
        $validated = $request->validated();

        Review::create([
            'book_id' => $book->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', compact('book'))->with('success', 'レビューを投稿しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Review $review): View
    {
        return view('reviews.edit', compact('review'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();
        $book = $review->book;

        $review->update([
            'book_id' => $book->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', compact('book'))->with('success', 'レビューを更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->delete();

        return redirect()->back();
    }
}
