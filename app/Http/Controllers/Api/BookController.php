<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する API エンドポイント。
     * キーワード検索、ジャンル絞り込み、ページネーションに対応。
     *
     * @param IndexBookRequest $request バリデーション済みの検索条件リクエスト
     * @return JsonResponse 書籍一覧とメタ情報を含む JSON レスポンス
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        $query = Book::with(['genres', 'reviews'])->withCount('reviews')->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('title', 'like', "%{$keyword}%");
        }

        if ($request->filled('genres')) {
            $genreIds = Genre::whereIn('name', $request->input('genres'))->pluck('id');
            $query->whereHas('genres', function ($q) use ($genreIds) {
                $q->whereIn('genre_id', $genreIds);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $books = $query->paginate($perPage);

        return response()->json([
            'data' => BookResource::collection($books)->resolve(),
            'meta' =>[
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ]
        ]);
    }

    /**
     * 書籍を登録する API エンドポイント。
     * Bookモデルを作成し、ジャンルを同期させてから BookDetailResource を返す。
     *
     * @param StoreBookRequest $request 書籍登録用のバリデーション済みリクエスト
     * @return JsonResponse 作成された書籍の詳細情報 (201 Created)
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $genreIds = Genre::whereIn('name', $validated['genres'])->pluck('id');
        $book->genres()->sync($genreIds);
        $book->load(['genres']);

        return (new BookDetailResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍詳細情報を取得する API エンドポイント。
     * 紐づくジャンルとレビューを読み込んで BookDetailResource を返す。
     *
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return BookDetailResource 書籍の詳細情報
     */
    public function show(Book $book): BookDetailResource
    {
        $book->load(['genres', 'reviews']);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を更新する API エンドポイント。
     * 入力値で Book を更新し、ジャンルを同期したうえで
     * 更新後の書籍情報を BookDetailResource として返す。
     *
     * @param UpdateBookRequest $request 書籍更新用のバリデーション済みリクエスト
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return BookDetailResource 更新された書籍の詳細情報
     */
    public function update(UpdateBookRequest $request, Book $book): BookDetailResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $genreIds = Genre::whereIn('name', $validated['genres'])->pluck('id');
        $book->genres()->sync($genreIds);
        $book->load(['genres']);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を削除する API エンドポイント。
     *
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return JsonResponse 削除成功時は 204 No Content を返す
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
