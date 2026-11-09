<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Exam, ExamReview, User, AccessAudit};
use App\Services\{ExamReviewWorkflow, ExamCertificates, RoleAccess};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamReviewController extends Controller
{
    public function index(Request $request, ExamCertificates $access)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['submitted', 'revision', 'approved', 'draft'])],
        ]);
        $baseQuery = RoleAccess::managesUsers($request->user())
            ? Exam::query()
            : $access->visibleExams($request->user());
        $statusCounts = (clone $baseQuery)
            ->selectRaw("COALESCE(review_status, 'draft') AS bucket, COUNT(*) AS aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $query = clone $baseQuery;
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)
                ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term)));
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'draft') {
                $query->where(fn ($q) => $q->whereNull('review_status')->orWhere('review_status', 'draft'));
            } else {
                $query->where('review_status', $filters['status']);
            }
        }

        $examQuery = $query->with('province')->withCount('participants')
            ->orderByRaw("CASE WHEN review_status = 'submitted' THEN 0 ELSE 1 END")
            ->latest();
        $exams = \App\Support\PageSize::paginate($examQuery, $request, 10);

        return view('admin.exam-reviews.index', [
            'exams' => $exams,
            'statusCounts' => $statusCounts,
            'totalCount' => $statusCounts->sum(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Exam $exam, ExamCertificates $access)
    {
        if (!RoleAccess::managesUsers($request->user())) $access->authorize($request->user(), $exam);
        $history = ExamReview::with('actor')->where('exam_id', $exam->id)->latest('id')->get();
        $submission = $history->firstWhere('status', 'submitted');
        // Regional viewers see only the participants in their own jurisdiction, including snapshots.
        if ($submission) {
            $allowed = $access->participants($request->user(), $exam)->pluck('user_id')->all();
            $snapshot = $submission->snapshot;
            $snapshot['participants'] = array_values(array_filter($snapshot['participants'], fn ($p) => in_array($p['member_id'], $allowed)));
            $submission->snapshot = $snapshot;
        }
        $canAssign = RoleAccess::managesUsers($request->user());
        $assigned = $exam->reviewers()->pluck('users.id')->all();
        $reviewers = $canAssign ? $this->eligible($exam)->orderBy('name')->get() : collect();
        $canReview = $request->user()->hasRole('verifikator_ujian_pb') && in_array($request->user()->id, $assigned)
            && !$exam->examiners()->where('users.id', $request->user()->id)->exists();
        $participantPhotos = $submission
            ? User::whereIn('id', array_column($submission->snapshot['participants'], 'member_id'))->pluck('photo', 'id')
            : collect();
        return view('admin.exam-reviews.show', compact('exam', 'history', 'submission', 'canAssign', 'assigned', 'reviewers', 'canReview', 'participantPhotos'));
    }

    public function assign(Request $request, Exam $exam)
    {
        abort_unless(RoleAccess::managesUsers($request->user()), 403);
        if (!$request->input('reviewer_ids')) $request->merge(['reviewer_ids' => []]);
        DB::transaction(function () use ($request, $exam) {
            $exam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            abort_if($exam->review_status === 'approved', 422, 'Penugasan hasil yang disetujui sudah dikunci.');
            $data = $request->validate(['reviewer_ids' => 'present|array',
                'reviewer_ids.*' => ['integer', 'distinct', Rule::in($this->eligible($exam)->pluck('id')->all())]]);
            $before = $exam->reviewers()->pluck('users.id')->all();
            $exam->reviewers()->sync($data['reviewer_ids']);
            AccessAudit::record('exam.reviewers.assigned', $request->user(), null, ['reviewer_ids' => $before], ['reviewer_ids' => $data['reviewer_ids']], $exam->id);
        });
        return back()->with('success', 'Verifikator Ujian PB ditugaskan ke sesi ini.');
    }

    public function store(Request $request, Exam $exam, ExamReviewWorkflow $workflow)
    {
        abort_if($request->session()->has('impersonation'), 403, 'Kembali ke akun sendiri sebelum melakukan verifikasi.');
        $data = $request->validate(['status' => 'required|in:approved,revision', 'notes' => 'required_if:status,revision|nullable|string|max:5000']);
        $workflow->review($request->user(), $exam, $data['status'], $data['notes'] ?? null);
        return back()->with('success', $data['status'] === 'approved' ? 'Hasil ujian disetujui pusat.' : 'Hasil dikembalikan kepada panitia untuk diperbaiki.');
    }

    private function eligible(Exam $exam)
    {
        $excluded = $exam->examiners()->pluck('users.id')->merge(ExamReview::where('exam_id', $exam->id)->where('status', 'submitted')->pluck('actor_id'))->filter()->unique()->all();
        return User::where(fn ($q) => $q->where('role', 'verifikator_ujian_pb')->orWhereJsonContains('roles', 'verifikator_ujian_pb'))
            ->where('is_active', true)->whereNotIn('id', $excluded);
    }
}
