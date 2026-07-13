<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧画面の表示
     * キーワード、ジャンル、並び順でフィルタリングできる
     *
     * @param Request $request 検索条件
     * @return View 一覧画面
     */
    public function index(IndexBookRequest $request): View
    {
        $genres = Genre::all();
        $query = Book::with('genres');

        // keyword
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // genre
        if ($request->filled('genre')) {
            $genreId = $request->input('genre');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // sort
        if ($request->filled('sort')) {
            $sort = $request->input('sort');
            $query = match ($sort) {
                default  => $query->orderBy('created_at', 'desc'),
                'oldest' => $query->orderBy('created_at', 'asc'),
                'rating' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->orderBy('id', 'asc'),
                'title'  => $query->orderBy('title', 'asc'),
            };
        }

        $books = $query->paginate(10)->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', compact('book'))->with('success', '書籍を​登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): View
    {
        $genres = $book->genres;

        return view('books.show', compact('book', 'genres'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', compact('book'))->with('success', '書籍を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

    /**
     * Google Books Api から書籍情報を取得する
     * 入力されたISBNから検索する
     *
     * @param string $isbn 入力されたISBN
     * @return array 書籍情報
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        // 入力されたisbnの前後の余白を除去
        $isbn = trim($isbn);

        // 無効な入力を早めにはじくための処理
        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 400);
        }

        // try{} エラーが起きるかもしれない処理を書いてる
        try {
            // laravel標準搭載のHTTPクライアントを使用して google books api にHTTPリクエスト送信
            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', [
                // 公式Docに記載されている検索方法
                'q' => 'isbn:' . $isbn,
                // 見つかったら一件だけ取得
                'maxResults' => 1,
                'key' => config('services.google_books.key')
            ]);

            // 上記の処理に失敗した時に返すエラー
            if (! $response->successful()) {
                return response()->json(['error' => '書籍情報の取得に失敗しました。'], 502);
            }

            // API から帰ってきたJSONデータを$itemsに入れてる 配列なのは返ってきたJSONデータが配列だから
            $items = $response->json('items', []);
            // 配列から本のデータを取りだす ['volumeInfo']は書かないと本以外のデータも取得してしまう
            // 公式DocでレスポンスJSONを確認すると分かりやすい
            $volumeInfo = $items[0]['volumeInfo'] ?? [];

            // 空だった場合のエラーメッセージ
            if (empty($volumeInfo)) {
                return response()->json(['error' => '該当する書籍が見つかりませんでした。'], 404);
            }

            // volumeInfoからimageLinks配列を取得
            $imageLinks = $volumeInfo['imageLinks'] ?? [];
            // thumbnailサイズを取得、なければsmallThumbnailサイズを取得
            $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;

            // フロントエンドにJSONデータを返す
            return response()->json([
                'title' => $volumeInfo['title'] ?? null,
                // 配列からデータを安全に取得する ドット記法で
                'author' => data_get($volumeInfo, 'authors.0'),
                'description' => $volumeInfo['description'] ?? null,
                'published_date' => $volumeInfo['publishedDate'] ?? null,
                'image_url' => $imageUrl,
            ]);
        // 予期しないエラー(通信系、APIが落ちてる、JSONが壊れてる等)が投げられたときに返すエラーメッセージ
        } catch (\Throwable $e) {
            return response()->json(['error' => '通信エラーが発生しました。'], 500);
        }
    }
}
