<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BookService
{
    /**
     * 書籍一覧の取得
     * キーワード、ジャンル、並び順による絞込に対応する
     *
     * @param  array  $filters  検索条件
     * @param  int  $perPage  1ページあたりの件数
     * @return LengthAwarePaginator ページネーションされた書籍一覧
     */
    public function getBooks(array $filters = [], int $perPage = 10, bool $isApi = false): LengthAwarePaginator
    {
        $query = Book::with('genres');

        if ($isApi) {
            // APIレスポンスで使用するレビュー件数と平均評価を取得
            $query->withCount('reviews')->withAvg('reviews', 'rating');
            $query->orderBy('id', 'asc');
        }

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['genre'])) {
            $genreId = $filters['genre'];
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        if (! empty($filters['genreId'])) {
            $genreId = $filters['genreId'];
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        if ($isApi === false) {
            $sort = $filters['sort'] ?? null;
            match ($sort) {
                'oldest' => $query->orderBy('created_at', 'asc'),
                'rating' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->orderBy('id', 'asc'),
                'title' => $query->orderBy('title', 'asc'),
                default => $query->orderBy('created_at', 'desc'),
            };
        }

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
     * ジャンルIDまたはジャンル名の配列をジャンルIDのコレクションに変換する
     *
     * @param  array  $genres  ジャンルIDまたはジャンル名の配列
     * @return Collection ジャンルIDのコレクション
     */
    private function resolveGenreIds(array $genres): Collection
    {
        return Genre::whereIn('id', $genres)
            ->orWhereIn('name', $genres)
            ->pluck('id');
    }

    /**
     * 書籍の新規作成とジャンルの紐付け
     *
     * @param  array  $data  書籍登録データ
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

            $genreIds = $this->resolveGenreIds($data['genres']);
            $book->genres()->sync($genreIds);
            $book->load('genres');

            return $book;
        });
    }

    /**
     * 書籍の更新とジャンルの紐付け
     *
     * @param  array  $data  書籍更新データ
     * @param  Book  $book  更新対象の書籍
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

            $genreIds = $this->resolveGenreIds($data['genres']);
            $book->genres()->sync($genreIds);
            $book->load('genres');

            return $book;
        });
    }

    /**
     * ISBNからGoogle Books APIで書籍情報を取得する
     *
     * @param  string  $isbn  ISBNコード
     * @return array 書籍情報
     */
    public function fetchBookByIsbn(string $isbn): array
    {
        $isbn = trim($isbn);

        if (strlen($isbn) !== 13) {
            return ['error' => 'ISBNは13桁で入力してください。', 'status' => 400];
        }

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:'.$isbn,
                'maxResults' => 1,
                'key' => config('services.google_books.key'),
            ]);

            if (! $response->successful()) {
                return ['error' => '書籍情報の取得に失敗しました。', 'status' => 500];
            }

            $items = $response->json('items', []);
            $volumeInfo = $items[0]['volumeInfo'] ?? [];

            if (empty($volumeInfo)) {
                return ['error' => '書籍が​見つかりませんでした。', 'status' => 404];
            }

            $imageLinks = $volumeInfo['imageLinks'] ?? [];
            $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;

            return [
                'title' => $volumeInfo['title'] ?? null,
                'author' => data_get($volumeInfo, 'authors.0'),
                'description' => $volumeInfo['description'] ?? null,
                'published_date' => $volumeInfo['publishedDate'] ?? null,
                'image_url' => $imageUrl,
            ];
        } catch (\Throwable $e) {
            return ['error' => '通信エラーが発生しました。', 'status' => 500];
        }
    }
}
