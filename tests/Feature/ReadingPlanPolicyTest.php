<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_更新_削除_読了は所有者のみ許可される(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create([
            'target_date' => today()->addDays(5),
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $this->assertTrue($owner->can('update', $plan));
        $this->assertTrue($owner->can('delete', $plan));
        $this->assertTrue($owner->can('complete', $plan));
        $this->assertFalse($other->can('update', $plan));
        $this->assertFalse($other->can('delete', $plan));
        $this->assertFalse($other->can('complete', $plan));
    }

    public function test_他人は読書計画の編集画面を開けない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create([
            'target_date' => today()->addDays(5),
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $this->actingAs($other)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();
    }

    public function test_他人は書籍を更新できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create([
            'target_date' => today()->addDays(5),
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $this->actingAs($other)
            ->put(route('reading-plans.update', $plan), ['target_date' => today()->addDays(7)])
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['target_date' => today()->addDays(5)]);
    }

    public function test_他人は書籍を削除できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create([
            'target_date' => today()->addDays(5),
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $this->actingAs($other)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_他人は書籍を読了にできない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create([
            'target_date' => today()->addDays(5),
            'status' => ReadingPlanStatus::Progress->value,
        ]);

        $this->actingAs($other)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['status' => ReadingPlanStatus::Progress->value]);
    }
}
