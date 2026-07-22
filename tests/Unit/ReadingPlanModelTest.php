<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画のリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach($genre);
        $readingPlan = ReadingPlan::factory()->for($user)->for($book)->create();

        $this->assertTrue($readingPlan->user->is($user));
        $this->assertTrue($readingPlan->book->is($book));
    }
}
