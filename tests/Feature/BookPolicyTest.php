<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_更新と削除は所有者だけが許可される(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create();
        $book->genres()->attach($genre);

        $this->assertTrue($owner->can('update', $book));
        $this->assertTrue($owner->can('delete', $book));
        $this->assertFalse($other->can('update', $book));
        $this->assertFalse($other->can('delete', $book));
    }

    public function test_他人は書籍の編集画面を開けない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create();
        $book->genres()->attach($genre);

        $this->actingAs($other)
            ->get(route('books.edit', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_他人は書籍を削除できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create();
        $book->genres()->attach($genre);

        $this->actingAs($other)
            ->delete(route('books.destroy', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
