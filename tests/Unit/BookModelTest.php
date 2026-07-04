<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍のリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $genres = Genre::factory()->create(3);
        $book = Book::factory()->for($user)->for($genres)->create();
        $review = Review::factory()->for($anotherUser)->for($book)->create();
        $book->favoritedUsers()->attach($anotherUser);
        $book->genres()->attach($genres);

        $this->assertTrue($book->user->is($user));
        $this->assertTrue($book->genres->is($genres));
        $this->assertTrue($book->reviews->is($review));
        $this->assertTrue($book->favoritedUsers()->is($anotherUser));
    }
}
