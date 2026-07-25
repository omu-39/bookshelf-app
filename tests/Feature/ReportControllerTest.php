<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_マイ書籍レポート画面を表示できる(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(3)->create();
        $books = Book::factory()->count(5)->create();
        $books->each(function ($book) use ($genres, $user) {
            $book->genres()->attach($genres->random(1, 2));
            Review::factory()->for($book)->for($user)->count(rand(0, 1))->create();
            ReadingPlan::factory()->for($user)->for($book)->create(['status' => ReadingPlanStatus::Completed->value]);
        });

        $response = $this->actingAs($user)->get(route('reports.index'))->assertOk();
    }

    public function test_レビュー数が正しく集計される()
    {
        $user = User::factory()->create();

        Review::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $this->assertSame(3, $stats['summary']['total_reviews']);
            return true;
        });
    }
    public function test_読了冊数が正しく集計される()
    {
        $user = User::factory()->create();

        ReadingPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $this->assertSame(2, $stats['summary']['books_read']);
            return true;
        });
    }

    public function test_平均評価が正しく集計される()
    {
        $user = User::factory()->create();

        collect([
            ['rating' => 5],
            ['rating' => 3],
            ['rating' => 4],
        ])->each(fn($rating) => Review::factory()->create([
            'user_id' => $user->id,
            'rating' => $rating['rating'],
        ]));

        $response = $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $this->assertSame(4, $stats['summary']['average_rating']);
            return true;
        });
    }

    public function test_評価分布が正しく集計される()
    {
        $user = User::factory()->create();

        collect([
            ['rating' => 5],
            ['rating' => 5],
            ['rating' => 4],
            ['rating' => 2],
        ])->each(fn($rating) => Review::factory()->create([
            'user_id' => $user->id,
            'rating' => $rating['rating'],
        ]));

        $response = $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $this->assertSame([
                0, // ★1
                1, // ★2
                0, // ★3
                1, // ★4
                2, // ★5
            ], $stats['rating_distribution']->toArray());

            return true;
        });
    }

    public function test_高評価書籍が評価順に表示される()
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => 'Book1',
            'author' => 'Author1',
        ]);
        $book2 = Book::factory()->create([
            'title' => 'Book2',
            'author' => 'Author2',
        ]);
        $book3 = Book::factory()->create([
            'title' => 'Book3',
            'author' => 'Author3',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $this->assertCount(3, $stats['top_rated_books']);

            $this->assertSame('Book1', $stats['top_rated_books'][0]['title']);
            $this->assertSame(5, $stats['top_rated_books'][0]['rating']);

            $this->assertSame('Book2', $stats['top_rated_books'][1]['title']);
            $this->assertSame(4, $stats['top_rated_books'][1]['rating']);

            $this->assertSame('Book3', $stats['top_rated_books'][2]['title']);
            $this->assertSame(3, $stats['top_rated_books'][2]['rating']);

            return true;
        });
    }

    public function test_ジャンル別平均評価が正しく集計される()
    {
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create([
            'name' => '小説',
        ]);
        $genre2 = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        $book1->genres()->attach($genre1);
        $book2->genres()->attach([$genre1->id, $genre2->id]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $genres = collect($stats['genre_ratings'])->keyBy('name');

            $this->assertSame(4, $genres['小説']['average_rating']);
            $this->assertSame(2, $genres['小説']['count']);

            $this->assertSame(3, $genres['ビジネス']['average_rating']);
            $this->assertSame(1, $genres['ビジネス']['count']);

            return true;
        });
    }
}
