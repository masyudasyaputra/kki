<?php

namespace App\Services;

use App\Models\{Exam, User};

class ExamAuthority
{
    public const REGIONAL = ['pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab'];

    public function isDan(Exam $exam): bool
    {
        return $exam->exam_type === 'dan'
            || $exam->participants()->whereHas('targetBelt', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%hitam%']))->exists()
            || \App\Models\ExamScore::where('exam_id', $exam->id)->whereHas('newBeltLevel', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%hitam%']))->exists();
    }

    public function regional(User $actor, Exam $exam): bool
    {
        if ($this->isDan($exam) || !$actor->province_id || (int) $actor->province_id !== (int) $exam->province_id) return false;
        if ($actor->hasRole(['pengprov', 'admin_pengprov'])) {
            return !$exam->participants()->whereDoesntHave('user', fn ($q) => $q->where('province_id', $actor->province_id))->exists();
        }
        if (!$actor->hasRole(['pengcab', 'admin_pengcab']) || !$actor->city_id) return false;
        if ($exam->city_id && (int) $exam->city_id !== (int) $actor->city_id) return false;
        $outsiders = $exam->participants()->whereDoesntHave('user', fn ($q) => $q->where('province_id', $actor->province_id)->where('city_id', $actor->city_id))->exists();
        return !$outsiders && ($exam->city_id || $exam->participants()->exists());
    }

    public function canReview(User $actor, Exam $exam): bool
    {
        if (!$actor->is_active || $exam->examiners()->where('users.id', $actor->id)->exists()) return false;
        if (!$this->isDan($exam) && ($this->regional($actor, $exam) || RoleAccess::managesUsers($actor))) return true;
        return $actor->hasRole('verifikator_ujian_pb') && $exam->reviewers()->where('users.id', $actor->id)->exists();
    }

    public function canIssue(User $actor, Exam $exam): bool
    {
        if (RoleAccess::certificates($actor)) return true;
        return $this->isDan($exam) ? RoleAccess::certificates($actor) : $this->regional($actor, $exam);
    }

    public function destination(Exam $exam): string
    {
        return $this->isDan($exam) ? 'PP' : 'Pengprov / Pengcab';
    }
}
