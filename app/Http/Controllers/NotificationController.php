<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ジャンル一覧画面の表示
     * 
     * @return View ジャンル一覧
     */
    public function index(): View
    {
        $notifications = Auth::user()->notifications;;

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知の既読処理
     * 
     * @param string $id ルートパラメータから取得した対象通知のid
     * @return RedirectResponse
     */
    public function read(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->find($id);

        $notification->markAsRead();

        return redirect()->back();
    }
}
