<?php

namespace App\Support;

use App\Models\User;
use App\Services\MembershipReview;

class DashboardDestination
{
    public static function url(User $user): string
    {
        $route = match (true) {
            $user->hasRole(['super_admin', 'pb']) => 'admin.dashboard',
            $user->hasRole('dewan_guru_pusat') => 'admin.oversight',
            $user->hasRole(['pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']) => 'admin.dashboard',
            $user->hasRole(['verifikator_ujian_pb', 'pengelola_sertifikat_pb', 'panitia_ujian', 'penguji']) => 'admin.exam-reviews.index',
            $user->hasRole(array_keys(MembershipReview::ROLES)) => 'admin.verifications.index',
            default => 'member.dashboard',
        };

        return route($route, [], false);
    }
}
