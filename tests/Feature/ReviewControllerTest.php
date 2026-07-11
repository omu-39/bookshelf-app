<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーはレビューを投稿できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);

        $reviewContent = ['rating' => 5, 'comment' => 'テストコメント'];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_所有者はレビューを編集できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($user)->for($book)->create(['comment' => '更新前コメント']);

        $this->assertDatabaseHas('reviews', [
            'comment' => '更新前コメント',
        ]);

        $updateReview = ['rating' => '5', 'comment' => '更新後コメント'];

        $this->actingAs($user)->put(route('reviews.update', $review), $updateReview)
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'comment' => '更新後コメント',
        ]);
    }

    public function test_所有者はレビューを削除できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($user)->for($book)->create();

        $this->actingAs($user)->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_書籍が削除されたら紐づくレビューも削除される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->for($user)->create();
        $book->genres()->attach($genre);
        Review::factory()->for($book)->create();

        $this->assertDatabaseCount('reviews', 1);

        $this->actingAs($user)->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_認証ユーザーはレビューにいいねできる(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($user)->for($book)->create();

        $this->actingAs($anotherUser)->post(route('reviews.like', $review));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $anotherUser->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_未認証ユーザーはレビューを投稿できない(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => 4, 'comment' => 'test'];

        $this->post(route('reviews.store', $book), $reviewContent)
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_未評価だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => null, 'comment' => 'test'];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_評価が数値でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => 'string', 'comment' => 'test'];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_評価が1～5の整数でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => '6', 'comment' => 'test'];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_コメントが空だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => '5', 'comment' => null];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_コメントが文字列でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => '5', 'comment' => 1234];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_コメントが1001文字以上だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::Factory()->create();
        $book->genres()->attach($genre);
        $reviewContent = ['rating' => '5', 'comment' => str_repeat('あ', 1001)];

        $this->actingAs($user)->post(route('reviews.store', $book), $reviewContent)
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }
}
