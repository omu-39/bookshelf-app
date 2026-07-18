<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧画面の表示
     * 
     * @return View お気に入り一覧画面
     */
    public function index(): View
    {
        $books = Auth::user()->favoriteBooks()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入り機能
     * toggle()で切り替え
     * 
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return RedirectResponse 書籍詳細画面
     */
    public function toggle(Book $book): RedirectResponse
    {
        $user = Auth::user();
        $user->favoriteBooks()->toggle($book->id);

        return redirect()->route('books.show', compact('book'));
    }
}
