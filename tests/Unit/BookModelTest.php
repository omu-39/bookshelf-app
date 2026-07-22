<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
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
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $review = Review::factory()->for($anotherUser)->for($book)->create();
        $book->favoritedUsers()->attach($anotherUser);
        $book->genres()->attach($genre);
        $readingPlan = ReadingPlan::factory()->for($book)->create();

        $this->assertTrue($book->user->is($user));
        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($book->reviews->contains($review));
        $this->assertTrue($book->favoritedUsers->contains($anotherUser));
        $this->assertTrue($book->readingPlans->contains($readingPlan));
    }
}
