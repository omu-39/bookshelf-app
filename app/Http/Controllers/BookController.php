<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
            switch ($sort) {
                case 'newest':
                    $query->orderBy('published_date', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('published_date', 'asc');
                    break;
                case 'rating':
                    $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->orderBy('title', 'asc'); ;
                    break;
                case 'title':
                    $query->orderBy('title', 'asc');
                    break;
            }
        }

        $books = $query->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
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
}
