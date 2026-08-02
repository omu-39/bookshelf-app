<?php

namespace App\Services;


use App\Models\Book;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
    public function getGenres(): Collection
    {
        return Genre::all();
    }

    /**
     * 書籍の新規作成とジャンルの紐付け
     * 
     * @param array $data 書籍登録データ
     * @return Book 作成された書籍
     */
    public function createBook(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $book = Book::create([
                'user_id' => Auth::id(),
                'title' => $data['title'],
                'author' => $data['author'],
                'isbn' => $data['isbn'] ?? null,
                'published_date' => $data['published_date'] ?? null,
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
            ]);

            $book->genres()->sync($data['genres']);

            return $book;
        });
    }

    /**
     * 書籍の更新とジャンルの紐付け
     * 
     * @param array $data 書籍更新データ
     * @param Book $book 更新対象の書籍
     * @return Book 更新された書籍
     */
    public function updateBook(array $data, Book $book): Book
    {
        return DB::transaction(function () use ($data, $book) {
            $book->update([
                'title' => $data['title'],
                'author' => $data['author'],
                'isbn' => $data['isbn'] ?? null,
                'published_date' => $data['published_date'] ?? null,
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
            ]);

            $book->genres()->sync($data['genres']);

            return $book;
        });
    }
}