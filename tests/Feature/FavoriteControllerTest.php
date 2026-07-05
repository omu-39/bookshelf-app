<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーはお気に入り一覧を表示できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);

        $this->actingAs($user)->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_認証ユーザーは書籍をお気に入り登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);

        $this->actingAs($user)->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_お気に入りの解除ができる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);

        $this->actingAs($user)->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        $this->actingAs($user)->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_未認証ユーザーはお気に入り登録できない(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));
    }
}
