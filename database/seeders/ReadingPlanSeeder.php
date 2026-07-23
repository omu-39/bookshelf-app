<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'target_date' => today()->addDays(rand(4, 10)),
                'completed_at' => null,
                'status' => 2,
            ],
            [
                'target_date' => today()->addDays(rand(4, 10)),
                'completed_at' => today()->subDays(2),
                'status' => 1,
            ],
            [
                'target_date' => today()->addDays(3),
                'completed_at' => null,
                'status' => 2,
            ],
            [
                'target_date' => today(),
                'completed_at' => null,
                'status' => 2,
            ],
            [
                'target_date' => today()->subDays(3),
                'completed_at' => null,
                'status' => 2,
            ],
        ];
        $books = Book::all();
        $bookIds = $books->pluck('id')->shuffle()->values();

        // 山田太郎(確認用)のシーディング
        $mainUser = User::where('id',1)->first();

        foreach ($plans as $index => $plan) {
            ReadingPlan::factory()->create([
                'user_id' => $mainUser->id,
                'book_id' => $bookIds[$index],
                ...$plan,
            ]);
        }

        // 他のユーザーのシーディング
        $users = User::where('id', '!=', 1)->get();

        foreach($users as $index => $user) {
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $bookIds[$index],
            ]);
        }
    }
}
