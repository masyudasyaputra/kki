<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;

class ExamExaminerController extends Controller
{
    public function edit(Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorizeSessionChange(auth()->user(), $exam);
        $examiners = $this->availableExaminers()->with(['province', 'dojo'])->orderBy('name')->get();
        $selected = $exam->examiners()->pluck('users.id')->toArray();

        return view('admin.exams.examiners', compact('exam', 'examiners', 'selected'));
    }

    public function update(Request $request, Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorizeSessionChange($request->user(), $exam);
        app(\App\Services\ExamReviewWorkflow::class)->assertEditable($exam);
        $request->validate([
            'examiner_ids' => 'nullable|array',
            'examiner_ids.*' => ['integer', \Illuminate\Validation\Rule::in($this->availableExaminers()->pluck('id')->all())],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $exam) {
            $ids = $request->examiner_ids ?? [];
            abort_if($exam->reviewers()->whereIn('users.id', $ids)->exists(), 422, 'Verifikator sesi tidak boleh menjadi panitia atau penguji sesi yang sama.');
            $before = $exam->examiners()->pluck('users.id')->all();
            $exam->examiners()->sync($ids);
            \App\Models\AccessAudit::record('exam.committee.assigned', $request->user(), null, ['user_ids' => $before], ['user_ids' => $ids], $exam->id);
        });

        return back()->with('success', 'Tim penguji dan panitia berhasil diperbarui.');
    }

    private function availableExaminers()
    {
        return User::where(fn ($q) => $q->whereIn('role', ['penguji', 'panitia_ujian'])
            ->orWhereJsonContains('roles', 'panitia_ujian')->orWhereJsonContains('roles', 'penguji'))
            ->when(!auth()->user()->hasRole('pb'), fn ($q) => $q->where('province_id', auth()->user()->province_id ?? 0));
    }
}
