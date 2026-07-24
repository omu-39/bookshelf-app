<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function index(Request $request): View
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
     * 書籍登録画面の表示
     * 
     * @return View
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍の登録
     * 書籍の新規作成とジャンルの紐付け
     * 
     * @param StoreBookRequest $request 書籍登録データ
     * @return RedirectResponse 詳細画面
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated) {
            $book = Book::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        return redirect()->route('books.show', compact('book'))->with('success', '書籍を​登録しました。');
    }

    /**
     * 書籍詳細画面の表示
     * 
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return View 詳細画面
     */
    public function show(Book $book): View
    {
        $genres = $book->genres;

        return view('books.show', compact('book', 'genres'));
    }

    /**
     * 書籍編集画面の表示
     * 
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return View 詳細画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍の更新
     * 内容の更新とジャンルを紐づけ
     * 
     * @param UpdateBookRequest $request 更新データ
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return RedirectResponse 詳細画面
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $book)
        {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        return redirect()->route('books.show', compact('book'))->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍の削除
     * 
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return RedirectResponse 一覧画面
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
        $isbn = trim($isbn);

        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 400);
        }

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:' . $isbn,
                'maxResults' => 1,
                'key' => config('services.google_books.key')
            ]);

            if (! $response->successful()) {
                return response()->json(['error' => '書籍情報の取得に失敗しました。'], 500);
            }

            $items = $response->json('items', []);
            $volumeInfo = $items[0]['volumeInfo'] ?? [];

            if (empty($volumeInfo)) {
                return response()->json(['error' => '書籍が​見つかりませんでした。'], 404);
            }

            $imageLinks = $volumeInfo['imageLinks'] ?? [];
            $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;

            return response()->json([
                'title' => $volumeInfo['title'] ?? null,
                'author' => data_get($volumeInfo, 'authors.0'),
                'description' => $volumeInfo['description'] ?? null,
                'published_date' => $volumeInfo['publishedDate'] ?? null,
                'image_url' => $imageUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => '通信エラーが発生しました。'], 500);
        }
    }
}
