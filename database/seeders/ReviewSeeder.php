<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($books as $book) {
            $randomUsers = $users->random(rand(2, 4));

            foreach ($randomUsers as $user) {
                Review::create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                    'rating' => rand(3, 5),
                    'comment' => fake()->paragraph(),
                ]);
            }
        }
    }
}
