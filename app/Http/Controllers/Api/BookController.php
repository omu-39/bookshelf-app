<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * 書籍一覧を取得する API エンドポイント。
     * キーワード検索、ジャンル絞り込み、ページネーションに対応。
     *
     * @param  IndexBookRequest  $request  検索条件リクエスト
     * @return JsonResponse 書籍一覧とメタ情報を含む JSON レスポンス
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->input('keyword'),
            'genreId' => $request->input('genreId'),
        ];

        $perPage = (int) $request->input('per_page', 10);
        // APIではレビュー件数・平均評価を返すため true を渡す
        $books = $this->bookService->getBooks($filters, $perPage, isApi: true);

        return response()->json([
            'data' => BookResource::collection($books)->resolve(),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ]);
    }

    /**
     * 書籍を登録する API エンドポイント。
     * Bookモデルを作成し、ジャンルを同期させてから BookDetailResource を返す。
     *
     * @param  StoreBookRequest  $request  書籍登録データ
     * @return JsonResponse 作成された書籍のデータ
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->createBook($request->validated());

        return (new BookDetailResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍詳細情報を取得する API エンドポイント。
     * 紐づくジャンルとレビューを読み込んで BookDetailResource を返す。
     *
     * @param  Book  $book  ルートパラメータから取得したBookオブジェクト
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
     * @param  UpdateBookRequest  $request  書籍更新用のバリデーション済みリクエスト
     * @param  Book  $book  ルートパラメータから取得したBookオブジェクト
     * @return BookDetailResource 更新された書籍の詳細情報
     */
    public function update(UpdateBookRequest $request, Book $book): BookDetailResource
    {
        $this->authorize('update', $book);

        $book = $this->bookService->updateBook($request->validated(), $book);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を削除する API エンドポイント。
     *
     * @param  Book  $book  ルートパラメータから取得したBookオブジェクト
     * @return JsonResponse 削除成功時は 204 No Content を返す
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
