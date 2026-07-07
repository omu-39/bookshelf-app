<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧情報を_jso_n形式で取得できる(): void
    {
        $genres = Genre::factory()->count(5)->create();
        $books = Book::factory()->count(10)->create();
        $books->each(function ($book) use ($genres) {
            $book->genres()->attach($genres->random(1, 3));
            Review::factory()->for($book)->count(3)->create();
        });

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'title', 'author', 'image_url', 'genres', 'average_rating', 'reviews_count'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
        ]);
    }

    public function test_キーワードで絞り込める(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->create(['title' => 'タイトルの検索'])->genres()->attach($genre);
        Book::factory()->create(['title' => 'Laravel教本'])->genres()->attach($genre);

        $response = $this->getJson('/api/v1/books?keyword=タイトル');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'タイトルの検索');
    }

    public function test_ジャンルで絞り込める(): void
    {
        $study = Genre::factory()->create(['name' => '勉強']);
        $game = Genre::factory()->create(['name' => 'ゲーム']);
        Book::factory()->create(['title' => 'Laravel教本'])->genres()->attach($study);
        Book::factory()->create(['title' => 'LaravelGame'])->genres()->attach($game);

        $response = $this->getJson('/api/v1/books?genres[]=勉強');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Laravel教本');
    }

    public function test_per_page_で件数を指定できる(): void
    {
        $genres = Genre::factory()->count(5)->create();
        $books = Book::factory()->count(10)->create();
        $books->each(function ($book) use ($genres) {
            $book->genres()->attach($genres->random(1, 3));
            Review::factory()->for($book)->count(3)->create();
        });

        $response = $this->getJson('/api/v1/books?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonPath('meta.total', 10);
    }

    public function test_per_pageが上限を超えると_422(): void
    {
        $genres = Genre::factory()->count(5)->create();
        $books = Book::factory()->count(10)->create();
        $books->each(function ($book) use ($genres) {
            $book->genres()->attach($genres->random(1, 3));
            Review::factory()->for($book)->count(3)->create();
        });

        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    public function test_書籍詳細を取得できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($user)->for($book)->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $book->id);
    }

    public function test_存在しない_i_dは_404_の_jso_nを返す(): void
    {
        $response = $this->getJson('/api/v1/books/999');

        $response->assertStatus(404);
        $response->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    public function test_書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => 'Webアプリケーション開発で広く使われている、非常に人気のあるPHPフレームワーク',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $response = $this->postJson('api/v1/books', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Laravel');

        $book = Book::where('title', 'Laravel')->first();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]);
    }

    public function test_不正な入力は_422_を返す(): void
    {
        $payload = [
            'title' => '',
            'description' => 'Webアプリケーション開発で広く使われている、非常に人気のあるPHPフレームワーク',
            'image_url' => null,
        ];

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id', 'title', 'author', 'isbn', 'published_date', 'genres']);
    }

    public function test_書籍を更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create(['title' => '更新前']);

        $payload = [
            'user_id' => $user->id,
            'title' => '更新後',
            'author' => 'user',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => 'updated',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', '更新後');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後']);
    }

    public function test_書籍を削除すると_204_を返す(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
    }
}
