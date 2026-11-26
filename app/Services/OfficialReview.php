<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OfficialReview
{
    public function applications(User $actor): Builder
    {
        $query = User::where(fn ($q) => $q->where('registration_type', 'official')->orWhereHas('official'));
        if (!RoleAccess::nationalOfficials($actor)) {
            $query->where('province_id', $actor->province_id ?: 0);
            if (!$actor->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi'])) {
                $query->where('city_id', $actor->city_id ?: 0);
            }
            foreach (RoleAccess::NATIONAL as $role) {
                $query->where('role', '!=', $role)
                    ->where(fn ($q) => $q->whereNull('roles')->orWhereJsonDoesntContain('roles', $role))
                    ->where(fn ($q) => $q->whereNull('positions')->orWhereJsonDoesntContain('positions', $role));
            }
        }
        return $query;
    }

    public function authorize(User $actor, User $target): void
    {
        abort_unless(RoleAccess::reviewsOfficials($actor), 403);
        if (RoleAccess::nationalOfficials($actor)) return;
        abort_unless($actor->province_id && (int) $target->province_id === (int) $actor->province_id && !$target->hasRole(RoleAccess::NATIONAL), 403);
        if (!$actor->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi'])) {
            abort_unless($actor->city_id && (int) $target->city_id === (int) $actor->city_id, 403);
        }
    }

    public function authorizeRegion(User $actor, array $data): void
    {
        if (RoleAccess::nationalOfficials($actor)) return;
        abort_unless($actor->province_id && (int) $data['province_id'] === (int) $actor->province_id, 403);
        if (!$actor->hasRole(['pengprov', 'admin_pengprov', 'verifikator_pengurus_provinsi'])) {
            abort_unless($actor->city_id && (int) ($data['city_id'] ?? 0) === (int) $actor->city_id, 403);
        }
    }

    public function pendingCount(User $actor): int
    {
        if (!RoleAccess::reviewsOfficials($actor)) return 0;
        $schema = $actor->getConnection()->getSchemaBuilder();
        if (!$schema->hasColumn('users', 'registration_type') || !$schema->hasColumn('officials', 'user_id')) return 0;
        return $this->applications($actor)->whereDoesntHave('official')->count();
    }
}
