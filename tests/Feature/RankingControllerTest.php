<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーはランキングを表示できる(): void
    {
        $genres = Genre::factory()->count(5)->create();
        $books = Book::factory()->count(10)->create();
        $books->each(function ($book) use ($genres) {
            $book->genres()->attach($genres->random(1, 3));
            Review::factory()->for($book)->count(3)->create();
        });

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee($books->pluck('name')->toArray());
    }

    public function test_ランキングは評価の高い順に並ぶ(): void
    {
        $genres = Genre::factory()->count(5)->create();
        $books = Book::factory()->count(3)->create();
        $rating = [5, 4, 3];
        $i = 0;

        $books->each(function ($book) use ($genres, $rating, &$i) {
            $book->genres()->attach($genres->random(1, 3));
            Review::factory()->for($book)->create(['rating' => $rating[$i]]);

            $i++;
        });

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder([5, 4, 3]);
    }
}
