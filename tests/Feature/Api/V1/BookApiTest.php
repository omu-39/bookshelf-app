<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧情報を_json_形式で取得できる(): void
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
                '*' => ['id', 'title', 'author', 'image_url', 'genres', 'average_rating', 'reviews_count'],
            ],
            'meta' => ['current_page','last_page', 'per_page', 'total'],
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

    public function test_書籍一覧取得時_keywordが255文字を超えると422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?keyword=' . str_repeat('a', 256));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['keyword']);
    }

    public function test_書籍一覧取得時_genresが配列でない場合は422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?genres=勉強');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres']);
    }

    public function test_書籍一覧取得時_存在しないジャンルを指定すると422を返す(): void
    {
        Genre::factory()->create(['name' => '勉強']);

        $response = $this->getJson('/api/v1/books?genres[]=存在しないジャンル');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genres.0']);
    }

    public function test_書籍一覧取得時_pageが1未満の場合は422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?page=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page']);
    }

    public function test_書籍一覧取得時_pageが整数でない場合は422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?page=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page']);
    }

    public function test_書籍一覧取得時_per_pageが1未満の場合は422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    public function test_書籍一覧取得時_per_pageが整数でない場合は422を返す(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    public function test_書籍一覧取得時_per_pageが上限を超えると422を返す(): void
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
        Review::factory()->for($user)->for($book)->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $book->id);
    }

    public function test_存在しない_idは_404_の_jsonを返す(): void
    {
        $response = $this->getJson('/api/v1/books/999');

        $response->assertStatus(404);
        $response->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    public function test_認証済みユーザーは書籍を登録できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            "title" => "Laravel",
            "author" => "Taylor Otwell",
            "isbn" => "1111111111111",
            "published_date" => "2011-01-01",
            "description" => "Webアプリケーション開発で広く使われている、非常に人気のあるPHPフレームワーク",
            "image_url" => null,
            "genres" => [$genre->name],
        ];

        $response = $this->postJson('api/v1/books', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Laravel');

        $book = Book::where('title', 'Laravel')->first();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]);
    }

    public function test_未認証ユーザーは書籍を登録できない(): void
    {
        $genre = Genre::factory()->create();

        $payload = [
            "title" => "Laravel",
            "author" => "Taylor Otwell",
            "isbn" => "1111111111111",
            "published_date" => "2011-01-01",
            "description" => "Webアプリケーション開発で広く使われている、非常に人気のあるPHPフレームワーク",
            "image_url" => null,
            "genres" => [$genre->name],
        ];

        $this->postJson('api/v1/books', $payload)
            ->assertStatus(401);

        $this->assertDatabaseMissing('books', ['title' => 'Laravel']);
    }

    public function test_書籍登録時_タイトルが未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => '',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_書籍登録時_タイトルが255文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => str_repeat('あ', 256),
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_書籍登録時_著者名が未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => '',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author']);
    }

    public function test_書籍登録時_著者名が255文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => str_repeat('あ', 256),
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author']);
    }

    public function test_書籍登録時_ISBNが13桁でない場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '123',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_書籍登録時_登録済みのISBNを指定した場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_書籍登録時_出版日が不正な形式の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '9999-99-99',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['published_date']);
    }

    public function test_書籍登録時_説明が512文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => str_repeat('あ', 513),
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_書籍登録時_画像URLが不正な形式の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => 'not-url',
            'genres' => [$genre->name],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image_url']);
    }

    public function test_書籍登録時_ジャンルが未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres']);
    }

    public function test_書籍登録時_ジャンルが配列でない場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => $genre->name,
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres']);
    }

    public function test_書籍登録時_存在しないジャンルを指定した場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => ['存在しないジャンル'],
        ];

        $this->postJson('/api/v1/books', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres.0']);
    }

    public function test_認証済みユーザーは書籍を更新できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create(['title' => '更新前']);

        $payload = [
            "title" => "更新後",
            "author" => "user",
            "isbn" => "1111111111111",
            "published_date" => "2011-01-01",
            "description" => "updated",
            "image_url" => null,
            "genres" => [$genre->name],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', '更新後');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後']);
    }

    public function test_未認証ユーザーは書籍を更新できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create(['title' => '更新前']);

        $payload = [
            "title" => "更新後",
            "author" => "user",
            "isbn" => "1111111111111",
            "published_date" => "2011-01-01",
            "description" => "updated",
            "image_url" => null,
            "genres" => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(401);

        $this->assertDatabaseMissing('books', ['title' => '更新後']);
    }

    public function test_書籍更新時_タイトルが未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => '',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_書籍更新時_タイトルが255文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => str_repeat('あ', 256),
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_書籍更新時_著者名が未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => '',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author']);
    }

    public function test_書籍更新時_著者名が255文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => str_repeat('あ', 256),
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author']);
    }

    public function test_書籍更新時_ISBNが13桁でない場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '123',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_書籍更新時_他の書籍と重複するISBNを指定した場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        Book::factory()->create(['isbn' => '1111111111111']);
        $book = Book::factory()->for($user)->create(['isbn' => '2222222222222']);

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_書籍更新時_自分のISBNのまま更新できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $book = Book::factory()->for($user)->create([
            'isbn' => '1111111111111',
        ]);

        $payload = [
            'title' => '更新後',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', '更新後');
    }

    public function test_書籍更新時_出版日が不正な形式の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '9999-99-99',
            'description' => '説明',
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['published_date']);
    }

    public function test_書籍更新時_説明が512文字を超える場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => str_repeat('あ', 513),
            'image_url' => null,
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_書籍更新時_画像URLが不正な形式の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => 'invalid-url',
            'genres' => [$genre->name],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image_url']);
    }

    public function test_書籍更新時_ジャンルが未入力の場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres']);
    }

    public function test_書籍更新時_ジャンルが配列でない場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => $genre->name,
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres']);
    }

    public function test_書籍更新時_存在しないジャンルを指定した場合は422を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = Book::factory()->for($user)->create();

        $payload = [
            'title' => 'Laravel',
            'author' => 'Taylor Otwell',
            'isbn' => '1111111111111',
            'published_date' => '2011-01-01',
            'description' => '説明',
            'image_url' => null,
            'genres' => ['存在しないジャンル'],
        ];

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres.0']);
    }

    public function test_認証済みユーザーは書籍を削除できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
    }

    public function test_未認証ユーザーは書籍を削除できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertStatus(401);

        $this->assertDatabaseCount('books', 1);
    }
}
