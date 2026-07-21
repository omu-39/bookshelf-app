<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            1 => '期待していた内容とは大きく違い、正直がっかりしました。',
            2 => 'ところどころ良い点はありましたが、全体的には物足りませんでした。',
            3 => '特別良いわけではないですが、時間つぶしにはちょうど良かったです。',
            4 => 'とても楽しめました。気になる点は少しありましたが満足です。',
            5 => '最高でした！また読み返したいと思える作品です。',
        ];

        $rating = fake()->numberBetween(1, 5);

        return [
            'book_id' => Book::factory(),
            'user_id' => User::factory(),
            'rating' => $rating,
            'comment' => $comments[$rating],
        ];
    }
}
