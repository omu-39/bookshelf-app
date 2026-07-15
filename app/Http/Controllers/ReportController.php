<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function PHPUnit\Framework\isEmpty;

class ReportController extends Controller
{
    /**
     * マイ読書レポート画面の表示
     *
     * ユーザーのレビュー情報から以下の統計値を生成してビューへ渡す:
     * - summary: 総レビュー数、読了冊数、平均評価
     * - rating_distribution: 評価1〜5の件数分布
     * - top_rated_books: 評価の高い順の上位5冊（重複なし）
     * - genre_ratings: ジャンル別の平均評価と件数（上位5件）
     *
     * @return View
     */
    public function index(): View
    {
        $reviews = Auth::user()->reviews()->with('book.genres')->get();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                // 読了冊数の仕様を要確認
                'books_read' => $reviews->unique('book_id')->count(),
                'average_rating' => $reviews->avg('rating'),
            ],
            'rating_distribution' => collect(range(1, 5))
                ->map(fn($rating) => $reviews->where('rating', $rating)->count())
                ->values(),
            'top_rated_books' => $reviews
            ->sortByDesc('rating')
            ->unique('book_id')
            ->take(5)
            ->map(fn($review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values(),
            'genre_ratings' => $reviews
                ->flatMap(fn($review) => $review->book->genres->map(fn($genre) => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'rating' => $review->rating,
                ]))
                ->groupBy('id')
                ->map(fn($item) => [
                    'id' => $item->first()['id'],
                    'name' => $item->first()['name'],
                    'average_rating' => $item->avg('rating'),
                    'count' => $item->count(),
                ])
                ->sortByDesc('average_rating')
                ->take(5)
                ->values(),
        ];

        return view('reports.index', compact('stats'));
    }
}
