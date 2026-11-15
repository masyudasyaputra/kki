<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OfficialReview
{
    public function applications(User $actor): Builder
    {
        $query = User::where('registration_type', 'official');
        if (!RoleAccess::managesUsers($actor)) {
            $query->where('province_id', $actor->province_id ?: 0);
            foreach (RoleAccess::NATIONAL as $role) {
                $query->where('role', '!=', $role)
                    ->where(fn ($q) => $q->whereNull('roles')->orWhereJsonDoesntContain('roles', $role))
                    ->where(fn ($q) => $q->whereNull('positions')->orWhereJsonDoesntContain('positions', $role));
            }
        }
        return $query;
    }

    public function pendingCount(User $actor): int
    {
        if (!$actor->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov'])) return 0;
        $schema = $actor->getConnection()->getSchemaBuilder();
        if (!$schema->hasColumn('users', 'registration_type') || !$schema->hasColumn('officials', 'user_id')) return 0;
        return $this->applications($actor)->whereDoesntHave('official')->count();
    }
}
