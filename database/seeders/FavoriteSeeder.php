<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach($users as $user) {
            $randomBooks = $books->random(rand(3, 5));

            $user->favoriteBooks()->syncWithoutDetaching($randomBooks->pluck('id'));
        }
    }
}
