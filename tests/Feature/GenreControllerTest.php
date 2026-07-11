<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーはジャンル一覧を表示できる(): void
    {
        $user = User::factory()->create();
        Genre::factory()->count(5)->create();

        $this->actingAs($user)->get(route('genres.index'))
            ->assertOk();
    }

    public function test_認証ユーザーはジャンル詳細画面を表示できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)->get(route('genres.show', $genre))
            ->assertOk();
    }

    public function test_認証ユーザーはジャンル登録できる(): void
    {
        $user = User::factory()->create();
        $content = ['name' => '新しいジャンル'];

        $this->actingAs($user)->post(route('genres.store'), $content)
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['name' => '新しいジャンル']);
    }

    public function test_認証ユーザーはジャンル編集できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '更新前ジャンル']);

        $this->assertDatabaseHas('genres', ['name' => '更新前ジャンル']);

        $updateGenre = ['name' => '更新後ジャンル'];

        $this->actingAs($user)->put(route('genres.update', $genre), $updateGenre)
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['name' => '更新後ジャンル']);
    }

    public function test_認証ユーザーはジャンル削除できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->assertDatabaseCount('genres', 1);

        $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_書籍が紐づいているジャンルは削除できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $this->actingAs($user)->delete(route('genres.destroy', $genre))
            ->assertSessionHas('error', 'この​ジャンルには​書籍が​紐付いている​ため削除できません。​');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_ジャンル名が体とバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genreContent = ['name' => null];

        $this->actingAs($user)->post(route('genres.store'), $genreContent)
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_ジャンル名が文字列でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genreContent = ['name' => 1234];

        $this->actingAs($user)->post(route('genres.store'), $genreContent)
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_ジャンル名は重複できない(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => 'ジャンル']);
        $genreContent = ['name' => 'ジャンル'];

        $this->actingAs($user)->post(route('genres.store'), $genreContent)
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }
}
