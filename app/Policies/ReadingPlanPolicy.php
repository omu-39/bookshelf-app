<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    public function edit(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    public function update(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    public function delete(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    public function complete(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }
}
