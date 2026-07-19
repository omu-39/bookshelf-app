<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧画面の表示
     * 読書の進行状況で絞込ができる
     *
     * @param Request $request リクエスト情報
     * @return View 読書計画一覧画面
     */
    public function index(Request $request): View
    {
        $query = ReadingPlan::query()
        ->with('book')
        ->where('user_id', Auth::id());

        // HTMLフォームは文字列を送るため数値へキャスト（"" → 0）
        $currentStatus = (int) $request->input('status');

        // Enum に存在する値だけフィルタする（存在しない値は「すべて」扱い）
        if (ReadingPlanStatus::tryFrom($currentStatus)) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query->get();

        return view('reading-plans.index', compact('currentStatus', 'readingPlans'));
    }

    /**
     * 読書計画作成画面の表示
     * 
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画の作成処理
     * 
     * @param StoreReadingPlanRequest $request 読書計画データ
     * @return RedirectResponse 読書計画一覧画面
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ReadingPlan::create([
            'user_id' => Auth::id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Unread,
        ]);

        return redirect()->route('reading-plans.index');
    }

    /**
     * 読書計画を読了状態に更新する
     *
     * @param ReadingPlan $plan ルートパラメータから取得した ReadingPlan オブジェクト
     * @return RedirectResponse 読書計画一覧画面
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index');
    }

    /**
     * 読書計画編集画面の表示
     *
     * @param ReadingPlan $plan ルートパラメータから取得した ReadingPlan オブジェクト
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('edit', $plan);

        $readingPlan = $plan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の更新
     *
     * @param UpdateReadingPlanRequest $request 更新内容
     * @param ReadingPlan $plan ルートパラメータから取得した ReadingPlan オブジェクト
     * @return RedirectResponse 読書計画一覧画面
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        $plan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画の削除
     *
     * @param ReadingPlan $plan ルートパラメータから取得した ReadingPlan オブジェクト
     * @return RedirectResponse 読書計画一覧画面
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
