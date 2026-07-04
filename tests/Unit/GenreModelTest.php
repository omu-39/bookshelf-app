<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ジャンルのリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $this->assertTrue($genre->books->contains($book));
    }
}
