<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーは書籍一覧を表示できる(): void
    {
        $genres = Genre::factory()->count(3)->create();
        $books = Book::factory()->count(5)->create();
        $books->each(function ($book) use ($genres) {
            $book->genres()->attach($genres->random(1, 3));
        });

        $response = $this->get(route('books.index'))->assertOk();
        $response->assertViewHas('books');
    }

    public function test_ユーザーは書籍詳細画面を表示できる(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach($genre);

        $this->get(route('books.show', $book))->assertOk();
    }

    public function test_認証ユーザーは書籍登録ができる(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $bookContent = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->post(route('books.store'), $bookContent);

        $book = Book::latest()->first();
        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'isbn' => '9781234567897',
            'user_id' => $user->id,
        ]);

        $genres->each(function ($genre) use ($book) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        });
    }

    public function test_未認証ユーザーは書籍登録できない(): void
    {
        $genre = Genre::factory()->create();
        $bookContent = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->post(route('books.store'), $bookContent)
            ->assertStatus(302);

        $response->assertRedirect(route('login'));
    }

    public function test_所有者は書籍編集ができる(): void
    {
        $user = User::factory()->create();
        $originalGenre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id, 'title' => '更新前タイトル']);
        $book->genres()->attach($originalGenre);

        $this->assertDatabaseHas('books', [
            'title' => '更新前タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $originalGenre->id,
        ]);

        $newGenre = Genre::factory()->create();
        $updateContent = [
            'title' => '更新後タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'description' => '更新された説明',
            'image_url' => $book->image_url,
            'genres' => [$newGenre->id],
        ];

        $response = $this->actingAs($user)->put(route('books.update', $book), $updateContent);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'title' => '更新後タイトル',
            'description' => '更新された説明',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_所有者は書籍削除ができる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $book->genres()->attach($genre);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_タイトルを空にするとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => null,
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_タイトルに文字列以外を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 1234,
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_タイトルに256文字以上を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => str_repeat('あ', 256),
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_著者名を空にするとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => null,
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('author');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_著者名に文字列以外を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => 1234,
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('author');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_著者名に256文字以上を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => str_repeat('あ', 256),
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('author');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_ISBNに文字列以外を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => '著者名',
            'isbn' => 1234,
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_ISBNは13桁でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => '著者名',
            'isbn' => '123456789',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_ISBNが重複しているとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['isbn' => '9781234567897']);

        $bookContent = [
            'title' => 'test',
            'author' => '著者名',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_出版日が有効な日付でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => '著者名',
            'isbn' => '1234567891234',
            'published_date' => '9999-99-99',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('published_date');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_説明に文字列以外を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'test',
            'author' => '著者名',
            'isbn' => '1234567891234',
            'published_date' => '2023-01-01',
            'description' => 1234,
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_画像URLが正しいURL形式でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'タイトル',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'image',
            'genres' => [1,2],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('image_url');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_ジャンルが未選択だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'タイトル',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'image',
            'genres' => null,
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('genres');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_ジャンルが配列でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'タイトル',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'image',
            'genres' => 1,
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('genres');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_存在しないジャンルを送信するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $bookContent = [
            'title' => 'タイトル',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'image',
            'genres' => [99],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('genres.*');

        $this->assertDatabaseCount('books', 0);
    }

    // 下記から応用で追加した機能のテスト

    public function test_キーワード検索で書籍を絞り込める(): void
    {
        collect(['Laravel', 'JavaScript'])
            ->each(fn ($title) => Book::factory()->create([
                'title' => $title,
        ]));

        $response = $this->get(route('books.index', ['keyword' => 'lara']))
            ->assertStatus(200);

        $response->assertSee('Laravel')
            ->assertDontSee('JavaScript');
    }

    public function test_ジャンルで書籍を絞り込める(): void
    {
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();
        $book1 = Book::factory()->create([
            'title' => 'Laravel',
        ]);
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create([
            'title' => 'JavaScript',
        ]);
        $book2->genres()->attach($genre2);

        // ジャンル 1 は小説
        $response = $this->get(route('books.index', ['genre' => $genre1->id]))
            ->assertStatus(200);

        $response->assertSee('Laravel', $genre1->name)
            ->assertDontSee('JavaScript', $genre2->name);
    }

    public function test_キーワードとジャンルの組み合わせで絞り込める(): void
    {
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();
        $book1 = Book::factory()->create([
            'title' => 'Laravel',
        ]);
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create([
            'title' => 'JavaScript',
        ]);
        $book2->genres()->attach($genre2);

        // ジャンル 1 は小説
        $response = $this->get(route('books.index', ['title' => 'lara', 'genre' => $genre1->id]))
            ->assertStatus(200);

        $response->assertSee('Laravel', $genre1->name)
            ->assertDontSee('JavaScript', $genre2->name);
    }

    public function test_書籍一覧は新しい順でソートできる(): void
    {
        collect([
            ['title' => 'Laravel', 'created_at' => now()],
            ['title' => 'JavaScript', 'created_at' => now()->addSeconds(3)],
            ['title' => 'PHP', 'created_at' => now()->addSeconds(5)],
        ])->each(fn ($data) => Book::factory()->create($data));

        $response = $this->get(route('books.index', ['sort' => 'newest']))
            ->assertStatus(200);

        $response->assertSeeInOrder([
            'PHP',
            'JavaScript',
            'Laravel',
        ]);
    }

    public function test_書籍一覧は古い順でソートできる(): void
    {
        collect([
            ['title' => 'Laravel', 'created_at' => now()],
            ['title' => 'JavaScript', 'created_at' => now()->addSeconds(3)],
            ['title' => 'PHP', 'created_at' => now()->addSeconds(5)],
        ])->each(fn ($data) => Book::factory()->create($data));

        $response = $this->get(route('books.index', ['sort' => 'oldest']))
            ->assertStatus(200);

        $response->assertSeeInOrder([
            'Laravel',
            'JavaScript',
            'PHP',
        ]);
    }

    public function test_書籍一覧はタイトル昇順でソートできる(): void
    {
        collect([
            ['title' => 'Laravel'],
            ['title' => 'JavaScript'],
            ['title' => 'PHP'],
        ])->each(fn($title) => Book::factory()->create($title));

        $response = $this->get(route('books.index', ['sort' => 'title']))
            ->assertStatus(200);

        $response->assertSeeInOrder([
            'JavaScript',
            'Laravel',
            'PHP',
        ]);
    }

    public function test_書籍一覧は評価順でソートできる(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'Laravel',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'JavaScript',
        ]);

        $book3 = Book::factory()->create([
            'title' => 'PHP',
        ]);

        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book3->id,
            'rating' => 4,
        ]);

        $response = $this->get(route('books.index', ['sort' => 'rating']))
            ->assertStatus(200);

        $response->assertSeeInOrder([
            'JavaScript',
            'PHP',
            'Laravel',
        ]);
    }

    public function test_ページを移動しても検索条件が維持されている(): void
    {
        Book::factory()
            ->count(11)
            ->sequence(
                fn ($sequence) => [
                    'title' => 'Laravel' . ($sequence->index + 1),
                ]
            )
            ->create();
        Book::factory()->create(['title' => 'PHP入門']);

        $response = $this->get(route('books.index', [
                'keyword' => 'Laravel',
                'page' => 2,
            ]))->assertOk();

        $response->assertSee('Laravel11')
            ->assertDontSee('PHP入門');
    }

    public function test_ISBN検索で書籍情報を取得できる(): void
    {
        $user = User::factory()->create();
        // 9784873115658 はリーダブルコードという書籍のISBN
        $response = $this->actingAs($user)->get('/books/isbn/9784873115658')
            ->assertOk();

        $response->assertJson(['title' => 'リーダブルコード']);
        $response->assertJsonStructure([
            'title',
            'author',
            'published_date',
            'description',
            'image_url',
        ]);
    }

    public function test_ISBN検索は13桁でないと検索できない(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/books/isbn/123456789');

        $response->assertStatus(400)
            ->assertJson(['error' => 'ISBNは13桁で入力してください。']);
    }

    public function test_GoogleBooksAPIが空のデータを返したら404エラーを返す(): void
    {
        $user = User::factory()->create();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 200),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9784088825250');

        $response->assertStatus(404)
            ->assertJson(['error' => '書籍が​見つかりませんでした。']);
    }

    public function test_GoogleBooksAPIがエラーレスポンスを返したら500エラーを返す(): void
    {
        $user = User::factory()->create();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9784873115658');

        $response->assertStatus(500)
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
            ]);
    }

    public function test_GoogleBooksAPIとの通信に失敗したら500エラーを返す(): void
    {
        $user = User::factory()->create();
        Http::fake(['https://www.googleapis.com/books/v1/volumes*' => function () {
                throw new ConnectionException();
            }
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9784873115658');

        $response->assertStatus(500)
            ->assertJson([
                'error' => '通信エラーが発生しました。',
            ]);
    }
}
