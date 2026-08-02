<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Book;
use App\Models\Genre;

class BookService
{
    /**
     * 書籍一覧の取得
     * キーワード、ジャンル、並び順による絞込に対応する
     *
     * @param array $filters 検索条件
     * @param int $perPage 1ページあたりの件数
     * @return LengthAwarePaginator ページネーションされた書籍一覧
     */
    public function getBooks(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Book::with('genres');

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['genre'])) {
            $genreId = $filters['genre'];
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $sort = $filters['sort'] ?? null;
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->orderBy('id', 'asc'),
            'title' => $query->orderBy('title', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * ジャンル一覧の取得
     *
     * @return Collection ジャンル一覧
     */
    public function getGenres()
    {
        return Genre::all();
    }
}