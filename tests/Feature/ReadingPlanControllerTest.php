<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画一覧画面を表示できる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('reading-plans.index'))
            ->assertOk();
    }

    public function test_読書計画一覧は状態で絞込みできる(): void
    {
        $user = User::factory()->create();
        $plan1 = ReadingPlan::factory()->for($user)->create(['status' => ReadingPlanStatus::Completed->value]);
        $plan2 = ReadingPlan::factory()->for($user)->create(['status' => ReadingPlanStatus::Progress->value]);

        $response = $this->actingAs($user)->get(route('reading-plans.index', ['status' => ReadingPlanStatus::Completed->value]))
            ->assertOk();

        $response->assertSee($plan1->book->title)
            ->assertDontSee($plan2->book->title);
    }

    public function test_未認証ユーザーがアクセスするとログイン画面にリダイレクトされる(): void
    {
        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));
    }

    public function test_読書計画作成画面を表示できる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('reading-plans.create'))
            ->assertOk();
    }

    public function test_読書計画を作成できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $plan = [
            'book_id' => $book->id,
            'target_date' => today()->addDays(5),
        ];

        $this->actingAs($user)->post(route('reading-plans.create'), $plan)
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_読書計画登録時_書籍を未選択の場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $plan = [
            'target_date' => today()->addDays(5),
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画登録時_存在しないbook_idはバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $plan = [
            'book_id' => 9999,
            'target_date' => today()->addDays(5),
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画登録時_book_idが整数以外の場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $plan = [
            'book_id' => 'abc',
            'target_date' => today()->addDays(5),
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画登録時_期日が未入力の場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $plan = [
            'book_id' => $book->id,
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画登録時_期日が日付形式でない場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $plan = [
            'book_id' => $book->id,
            'target_date' => 'abc',
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_読書計画登録時_期日が今日より前の場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $plan = [
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
        ];

        $this->actingAs($user)
            ->post(route('reading-plans.create'), $plan)
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_所有者は読書計画編集画面を表示できる(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();

        $this->actingAs($user)->get(route('reading-plans.edit', $plan))
            ->assertOk();
    }

    public function test_所有者は読書計画の期日を更新できる(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create(['target_date' => today()->addDay()]);

        $this->assertDatabaseHas('reading_plans', ['target_date' => today()->addDay()]);

        $this->actingAs($user)->put(route('reading-plans.update', $plan), ['target_date' => today()->addDays(5)])
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', ['target_date' => today()->addDays(5)]);
    }

    public function test_読書計画更新時_期日が未選択だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => '',
            ])->assertSessionHasErrors('target_date');
    }

    public function test_読書計画更新時_期日が日付形式でないとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => 'abc',
            ])
            ->assertSessionHasErrors('target_date');
    }

    public function test_読書計画更新時_期日が今日より前だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->subDay(),
            ])
            ->assertSessionHasErrors('target_date');
    }

    public function test_所有者は読書計画を削除できる(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();

        $this->actingAs($user)->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_所有者は読書計画を読了に更新できる(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create(['status' => ReadingPlanStatus::Progress->value]);

        $this->actingAs($user)->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', ['status' => ReadingPlanStatus::Completed->value]);
    }
}
