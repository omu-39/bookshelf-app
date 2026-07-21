<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $books->each(function ($book) use ($users) {
            $randomUsers = $users
                ->where('id', '!=', $book->user_id)
                ->random(rand(2, 4));

            foreach ($randomUsers as $user) {
                Review::factory()->create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                ]);
            }
        });
    }
}
