<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessAudit;
use App\Services\QualificationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QualificationReviewController extends Controller
{
    public function index(Request $request, QualificationReview $review)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'revision', 'expired'])], 'search' => ['nullable', 'string', 'max:255']]);
        $status = $filters['status'] ?? 'pending';
        $query = $review->applications($request->user())->with(['user', 'province', 'city']);
        if ($status === 'expired') $query->where('status', 'approved')->whereDate('expires_at', '<', today());
        else {
            $query->where('status', $status);
            if ($status === 'approved') $query->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()));
        }
        if (!empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(fn ($q) => $q->where('certificate_number', 'like', $term)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
        }
        $qualifications = $query->latest('id')->paginate(15)->withQueryString();
        return view('admin.qualifications.index', compact('qualifications', 'status'));
    }

    public function show(Request $request, int $qualification, QualificationReview $review)
    {
        $qualification = $review->applications($request->user())->with(['user', 'province', 'city', 'reviewer'])->findOrFail($qualification);
        return view('admin.qualifications.show', compact('qualification'));
    }

    public function store(Request $request, int $qualification, QualificationReview $review)
    {
        abort_if($request->session()->has('impersonation'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'revision'])], 'notes' => ['nullable', 'required_if:status,revision', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $qualification, $review, $data) {
            $record = $review->applications($request->user())->lockForUpdate()->findOrFail($qualification);
            abort_if($record->user_id === $request->user()->id, 403, 'Pengajuan sendiri harus diperiksa administrator lain.');
            if ($record->status !== 'pending') throw ValidationException::withMessages(['status' => 'Pengajuan sudah diperiksa. Muat ulang halaman.']);
            if ($data['status'] === 'approved' && $record->expires_at?->lt(today())) throw ValidationException::withMessages(['status' => 'Lisensi sudah kedaluwarsa. Minta perbaikan dokumen.']);
            $before = $record->only(['status', 'notes']);
            $record->update($data + ['reviewer_id' => $request->user()->id, 'reviewed_at' => now()]);
            AccessAudit::record('qualification.reviewed', $request->user(), $record->user, $before + ['qualification_id' => $record->id], $record->only(['status', 'notes']) + ['qualification_id' => $record->id]);
        });
        return redirect()->route('admin.qualifications.index')->with('success', 'Keputusan verifikasi Wasit/Juri disimpan.');
    }
}
