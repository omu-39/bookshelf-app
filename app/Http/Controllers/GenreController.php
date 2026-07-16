<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧画面の表示
     * 
     * @return View ジャンル一覧
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面の表示
     * 
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルの登録
     * 
     * @param StoreGenreRequest $request 登録内容
     * @return RedirectResponse ジャンル一覧
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    /**
     * ジャンル詳細画面の表示
     * 
     * @param Genre $genre ルートパラメータから取得したGenreオブジェクト
     * @return View 詳細画面
     */
    public function show(Genre $genre)
    {
        $books = $genre->books()->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面の表示
     * 
     * @param Genre $genre ルートパラメータから取得したGenreオブジェクト
     * @return View 編集画面
     */
    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルの更新
     * 
     * @param UpdateGenreRequest $request 更新データ
     * @param Genre $genre ルートパラメータから取得したGenreオブジェクト
     * @return RedirectResponse ジャンル一覧
     */
    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    /**
     * ジャンル削除
     * 紐づく書籍がある場合削除ガード
     * 
     * @param Genre $genre ルートパラメータから取得したGenreオブジェクト
     * @return RedirectResponse 前のページ
     */
    public function destroy(Genre $genre)
    {
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')->with('error', 'この​ジャンルには​書籍が​紐付いている​ため削除できません。​');
        }

        $genre->delete();

        return redirect()->back()->with('success', 'ジャンルを削除しました。');
    }
}
