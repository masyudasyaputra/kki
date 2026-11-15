<?php

namespace App\Services;

use App\Models\{User, OfficialQualification};
use Illuminate\Database\Eloquent\Builder;

class QualificationReview
{
    public const REVIEWERS = ['super_admin', 'pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab'];

    public function applications(User $actor): Builder
    {
        abort_unless($actor->hasRole(self::REVIEWERS), 403);
        $query = OfficialQualification::query();
        if (RoleAccess::managesUsers($actor)) return $query;
        $query->where('province_id', $actor->province_id ?: 0);
        if (!$actor->hasRole(['pengprov', 'admin_pengprov'])) $query->where('city_id', $actor->city_id ?: 0);
        return $query;
    }
}
