<?php

namespace Tests\Feature\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\OnDueDateNotification;
use App\Notifications\ThreeDaysAfterNotification;
use App\Notifications\ThreeDaysBeforeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlansCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日の3日前になると通知が送信される(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today()->addDays(3),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            ThreeDaysBeforeNotification::class
        );
    }

    public function test_期日の当日になると通知が送信される(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today(),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            OnDueDateNotification::class
        );
    }

    public function test_期日を3日過ぎると通知が送信される(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today()->subDays(3),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            ThreeDaysAfterNotification::class
        );
    }

    public function test_読了済みの計画には通知が送信されない(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today(),
                'status' => ReadingPlanStatus::Completed->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertNothingSent();
    }

    public function test_期日3日前の通知に正しい通知内容を送信できている(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today()->addDays(3),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            ThreeDaysBeforeNotification::class,
            function ($notification) use ($user, $plan) {

                $data = $notification->toArray($user);

                return $data['title'] === '読書計画の期限が近づいています'
                    && $data['body'] === "『{$plan->book->title}』の期限は {$plan->target_date->format('Y-m-d')} です。";
            }
        );
    }

    public function test_期日当日の通知に正しい通知内容を送信できている(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today(),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            OnDueDateNotification::class,
            function ($notification) use ($user, $plan) {

                $data = $notification->toArray($user);

                return $data['title'] === '読書計画の期限は本日です'
                    && $data['body'] === "『{$plan->book->title}』の期限は本日({$plan->target_date->format('Y-m-d')})です。";
            }
        );
    }

    public function test_期限切れ3日後の通知に正しい通知内容を送信できている(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today()->subDays(3),
                'status' => ReadingPlanStatus::Expired->value,
            ]);

        $this->artisan('reading-plans:check');

        Notification::assertSentTo(
            $user,
            ThreeDaysAfterNotification::class,
            function ($notification) use ($user, $plan) {

                $data = $notification->toArray($user);

                return $data['title'] === '読書計画の期限を過ぎました'
                    && $data['body'] === "『{$plan->book->title}』の期限({$plan->target_date->format('Y-m-d')})から3日過ぎました。";
            }
        );
    }

    public function test_期日を過ぎた読書計画の状態が期限切れに更新される(): void
    {
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)
            ->create([
                'target_date' => today()->subDay(),
                'status' => ReadingPlanStatus::Progress->value,
            ]);

        $this->artisan('reading-plans:check');

        $this->assertDatabaseHas('reading_plans', ['status' => ReadingPlanStatus::Expired->value]);
    }
}
