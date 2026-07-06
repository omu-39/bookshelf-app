<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(route('books.index'))->assertOk();
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

        $response = $this->post(route('books.store'), $bookContent);

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

        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_タイトルがないと書籍を登録できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $bookContent = [
            'title' => null,
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2023-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genre->id],
        ];

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_isb_nは重複して登録できない(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['isbn' => '9781234567897']);
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

        $this->actingAs($user)->post(route('books.store'), $bookContent)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }
}
