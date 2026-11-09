<?php

namespace App\Services;

use App\Models\{Exam, ExamReview, ExamScore, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamReviewWorkflow
{
    public function assertEditable(Exam $exam): void
    {
        abort_if(in_array($exam->review_status, ['submitted', 'approved'], true) || $exam->status === 'completed', 422,
            'Hasil ujian dikunci. Pusat harus mengembalikan pengajuan untuk perbaikan terlebih dahulu.');
    }

    public function snapshot(Exam $exam): array
    {
        return ['exam' => $exam->only(['id', 'name', 'exam_type', 'execution_date', 'location', 'province_id']),
            'participants' => $exam->participants()->with(['user', 'targetBelt'])->get()->map(fn ($p) => [
                'participant_id' => $p->id, 'member_id' => $p->user_id, 'name' => $p->user?->name,
                'member_number' => $p->user?->member_number, 'dojo' => $p->user?->dojo?->name,
                'target_belt' => $p->targetBelt?->display_name,
                'score' => ExamScore::where('exam_id', $exam->id)->where('member_id', $p->user_id)->first()?->toArray(),
            ])->all()];
    }

    public function submit(User $actor, Exam $exam): void
    {
        app(ExamCertificates::class)->authorizeCommittee($actor, $exam);
        DB::transaction(function () use ($actor, $exam) {
            $exam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($exam);
            $snapshot = $this->snapshot($exam);
            $errors = [];
            if (!$snapshot['participants']) $errors[] = 'Tambahkan peserta ujian terlebih dahulu.';
            foreach ($snapshot['participants'] as $p) {
                $score = $p['score'];
                foreach (ExamCertificates::materials($exam->exam_type) as $field => $label) {
                    if (($score[$field] ?? null) === null || $score[$field] === '') $errors[] = $p['name'].': nilai '.$label.' belum lengkap.';
                }
                if (!in_array($score['result'] ?? null, ['Lulus', 'Tidak Lulus', 'Percobaan'], true)) $errors[] = $p['name'].': hasil ujian belum ditentukan.';
            }
            if ($errors) throw ValidationException::withMessages(['submission' => $errors]);
            ExamReview::create(['exam_id' => $exam->id, 'actor_id' => $actor->id, 'status' => 'submitted', 'snapshot' => $snapshot]);
            $exam->forceFill(['review_status' => 'submitted'])->save();
        });
    }

    public function review(User $actor, Exam $exam, string $status, ?string $notes): void
    {
        abort_unless($actor->is_active && $actor->hasRole('verifikator_ujian_pb'), 403);
        DB::transaction(function () use ($actor, $exam, $status, $notes) {
            $exam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            abort_unless($exam->reviewers()->where('users.id', $actor->id)->exists(), 403, 'Anda belum ditugaskan sebagai verifikator sesi ini.');
            abort_if($exam->examiners()->where('users.id', $actor->id)->exists(), 403, 'Panitia atau penguji tidak dapat memverifikasi sesi yang sama.');
            abort_unless($exam->review_status === 'submitted', 422, 'Pengajuan sudah diproses atau belum dikirim.');
            abort_unless(in_array($status, ['approved', 'revision'], true), 422);
            $submission = ExamReview::where('exam_id', $exam->id)->where('status', 'submitted')->latest('id')->firstOrFail();
            abort_if((int) $submission->actor_id === (int) $actor->id, 403, 'Pengirim tidak dapat memverifikasi pengajuannya sendiri.');
            ExamReview::create(['exam_id' => $exam->id, 'actor_id' => $actor->id, 'status' => $status,
                'notes' => $notes, 'snapshot' => $submission->snapshot]);
            $exam->forceFill(['review_status' => $status, 'status' => $status === 'approved' ? 'completed' : $exam->status])->save();
        });
    }
}
