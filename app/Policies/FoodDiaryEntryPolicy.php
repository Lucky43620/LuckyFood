<?php

namespace App\Policies;

use App\Models\FoodDiaryEntry;
use App\Models\User;

class FoodDiaryEntryPolicy
{
    public function update(User $user, FoodDiaryEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, FoodDiaryEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
