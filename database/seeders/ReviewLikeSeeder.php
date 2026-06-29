<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $randomUsers = $users->random(rand(0, 3));

            $review->likedByUsers()->syncWithoutDetaching($randomUsers->pluck('id'));
        }
    }
}
