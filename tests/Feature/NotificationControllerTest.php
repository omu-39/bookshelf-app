<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ThreeDaysBeforeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_通知一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk();
    }

    public function test_未認証ユーザーは通知一覧画面を表示できない(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_所有者は通知を既読にできる(): void
    {
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)->create(['target_date' => today()->addDays(3)]);

        $notification = $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => ThreeDaysBeforeNotification::class,
            'data' => [
                'title' => '読書計画の期限が近づいています',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
