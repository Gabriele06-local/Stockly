<?php

namespace App\Policies;

use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function adjust(User $user): bool
    {
        return true; // staff can adjust, admin can do everything
    }

    public function transfer(User $user): bool
    {
        return $user->isAdmin();
    }
}
