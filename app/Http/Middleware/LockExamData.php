<?php

namespace App\Http\Middleware;

use App\Models\{Exam, ExamParticipant};
use App\Services\ExamReviewWorkflow;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LockExamData
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user();
        if ($actor->hasRole('panitia_ujian') && !$actor->hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo', 'penguji', 'verifikator_ujian_pb', 'pengelola_sertifikat_pb', 'dewan_guru_pusat', 'super_admin'])) {
            abort_unless($request->routeIs('admin.exam-reviews.*', 'admin.exams.index', 'admin.exams.show',
                'admin.exams.scoring', 'admin.exams.scoring.*', 'admin.exams.register-member',
                'admin.exams.remove-member', 'admin.exams.bulk-remove-member'), 403);
        }
        if (!$request->routeIs('admin.exams.update', 'admin.exams.destroy', 'admin.exams.update-result',
            'admin.exams.register-member', 'admin.exams.remove-member', 'admin.exams.bulk-remove-member',
            'admin.exams.scoring.store', 'admin.exams.examiners.update')) return $next($request);

        return DB::transaction(function () use ($request, $next) {
            $exam = $request->route('exam');
            $ids = $exam ? [$exam instanceof Exam ? $exam->id : $exam] : [];
            if ($participant = $request->route('participant')) {
                $participant = $participant instanceof ExamParticipant ? $participant : ExamParticipant::findOrFail($participant);
                $ids[] = $participant->exam_id;
            }
            if ($request->routeIs('admin.exams.bulk-remove-member')) {
                $request->validate(['participant_ids' => 'required|array', 'participant_ids.*' => 'integer|exists:exam_participants,id']);
                $ids = ExamParticipant::whereIn('id', $request->input('participant_ids'))->pluck('exam_id')->all();
            }
            foreach (Exam::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get() as $locked) {
                app(ExamReviewWorkflow::class)->assertEditable($locked);
            }
            return $next($request);
        });
    }
}
