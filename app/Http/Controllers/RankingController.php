<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * ランキング画面の表示
     * 書籍のレビュー件数、平均評価順にTOP10を取得
     * 
     * @return View ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::withCount('reviews')->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->limit(10)->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
