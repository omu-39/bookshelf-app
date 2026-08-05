<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $targetDate = fake()->dateTimeBetween('-1 days', '+7 days')->format('Y-m-d');
        $completedAt = fake()->optional(0.5)->dateTimeBetween('-5 days', $targetDate);

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => $targetDate,
            'completed_at' => $completedAt?->format('Y-m-d'),
            'status' => $completedAt ? 1 : 2,
        ];
    }
}
