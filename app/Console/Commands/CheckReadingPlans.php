<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ExpiredNotification;
use App\Notifications\OnDueDateNotification;
use App\Notifications\ThreeDaysBeforeNotification;
use Illuminate\Console\Command;

class CheckReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:check';

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
            ->with('book')
            ->each(fn($plan) => $plan->user?->notify(new $notification($plan)));
    }

    private function updateExpired()
    {
        ReadingPlan::where('target_date', '<', today())
            ->where('status', ReadingPlanStatus::Progress->value)
            ->update(['status' => ReadingPlanStatus::Expired->value]);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->notifyPlans(today()->addDays(3), ThreeDaysBeforeNotification::class);
        $this->notifyPlans(today(), OnDueDateNotification::class);
        $this->notifyPlans(today()->subDays(3), ExpiredNotification::class);
        $this->updateExpired();
    }
}
