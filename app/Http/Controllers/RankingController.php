<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $rankedBooks = Book::withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->limit(10)->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
