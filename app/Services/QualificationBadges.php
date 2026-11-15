<?php

namespace App\Services;

use App\Models\{User, OfficialQualification};

class QualificationBadges
{
    public function forUser(User $user): array
    {
        $empty = ['qualificationBadges' => [], 'cardQualificationLabel' => null];
        if (!$user->getConnection()->getSchemaBuilder()->hasTable('official_qualifications')) return $empty;
        $licenses = $user->qualifications()->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->latest('id')->get();
        $referee = $licenses->contains(fn ($license) => in_array($license->kind, ['referee', 'both'], true));
        $judge = $licenses->contains(fn ($license) => in_array($license->kind, ['judge', 'both'], true));
        return [
            'qualificationBadges' => $licenses->map(fn ($license) => (OfficialQualification::KINDS[$license->kind] ?? 'Wasit/Juri').' Terverifikasi · '.$license->license_level)->unique()->values()->all(),
            'cardQualificationLabel' => $referee && $judge ? 'WASIT & JURI' : ($referee ? 'WASIT' : ($judge ? 'JURI' : null)),
        ];
    }
}
