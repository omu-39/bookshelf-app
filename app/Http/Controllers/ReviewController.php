<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビューの投稿
     * 
     * @param StoreReviewRequest $request 投稿データ
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return RedirectResponse 書籍詳細画面
     */
    public function store(StoreReviewRequest $request, Book $book)
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
     * レビュー編集画面の表示
     * 
     * @param Revuew $review ルートパラメータから取得したReviewオブジェクト
     * @return View 編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューの更新
     * 
     * @param UpdateReviewRequest $request 更新データ
     * @param Review $review ルートパラメータから取得したReviewオブジェクト
     * @return RedirectResponse 書籍詳細画面
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
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
     * レビューの削除
     * 
     * @param Review $review ルートパラメータから取得したReviewオブジェクト
     * @return RedirectResponse 書籍詳細画面
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $book = $review->book;

        $review->delete();

        return redirect()->route('books.show', compact('book'))->with('success', 'レビューを削除しました。');
    }

    /**
     * レビューのいいね機能
     * toggle()で切り替え
     * 
     * @param Review $review ルートパラメータから取得したReviewオブジェクト
     * @return RedirectResponse 書籍詳細画面
     */
    public function like(Review $review): RedirectResponse
    {
        $book = $review->book;
        $user = Auth::user();
        $user->likedReviews()->toggle($review->id);

        return redirect()->route('books.show', compact('book'));
    }
}
