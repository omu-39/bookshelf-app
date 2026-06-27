<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Model\User;
use App\Model\Book;
use App\Model\Review;

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
                    'star_rating' => rand(3, 5),
                    'comment' => fake()->paragraph(),
                ]);
            }
        }
    }
}
