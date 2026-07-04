<?php

namespace Tests\Unit;

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーのリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $review = Review::factory()->for($anotherUser)->for($book)->create();
        $book->favoritedUsers()->attach($anotherUser);
        $book->genres()->attach($genre);
        $review->likedByUsers()->attach($user);

        $this->assertTrue($user->books->contains($book));
        $this->assertTrue($anotherUser->reviews->contains($review));
        $this->assertTrue($anotherUser->favoriteBooks->contains($book));
        $this->assertTrue($user->likedReviews->contains($review));
    }
}
