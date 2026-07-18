<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\DueSoonNotification;
use App\Notifications\DueTodayNotification;
use App\Notifications\ExpiredNotification;
use Illuminate\Console\Command;

class CheckReadingPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plan:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期日を確認して通知を送信';

    private function notifyPlans($date, $notification)
    {
        ReadingPlan::query()
            ->whereDate('target_date', $date)
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->with('user') // N+1防止
            ->each(fn($plan) => $plan->user?->notify(new $notification($plan)));
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->notifyPlans(today()->addDays(3), DueSoonNotification::class);
        $this->notifyPlans(today(), DueTodayNotification::class);
        $this->notifyPlans(today()->subDay(), ExpiredNotification::class);
    }
}
