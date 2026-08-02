<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * 書籍一覧画面の表示
     * キーワード、ジャンル、並び順による検索条件で絞り込む
     *
     * @param Request $request 検索条件
     * @return View 一覧画面
     */
    public function index(Request $request): View
    {
        $genres = $this->bookService->getGenres();
        $filters = $request->only(['keyword', 'genre', 'sort']);
        $books = $this->bookService->getBooks($filters);

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面の表示
     * 
     * @return View
     */
    public function create(): View
    {
        $genres = $this->bookService->getGenres();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍の新規登録
     * 
     * @param StoreBookRequest $request 書籍登録データ
     * @return RedirectResponse 詳細画面
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $book = $this->bookService->createBook($request->validated());

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

        $book = $this->bookService->updateBook($request->validated(), $book);

        return redirect()->route('books.show', compact('book'))->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍の削除
     * 
     * @param Book $book ルートパラメータから取得したBookオブジェクト
     * @return RedirectResponse 一覧画面
     */
    public function destroy(Book $book): RedirectResponse
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
     * @return JsonResponse 書籍情報
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        $bookData = $this->bookService->fetchBookByIsbn($isbn);

        if (isset($bookData['error'])) {
            return response()->json(['error' => $bookData['error']], $bookData['status']);
        }

        return response()->json($bookData);
    }
}
