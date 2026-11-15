<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MembershipReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipVerificationController extends Controller
{
    public function index(Request $request, MembershipReview $review)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'revision'])], 'search' => ['nullable', 'string', 'max:255']]);
        $base = $review->members($request->user());
        $counts = (clone $base)->selectRaw('verification_status, COUNT(*) as total')->groupBy('verification_status')->pluck('total', 'verification_status');
        $query = $base->with(['province', 'city', 'dojo', 'beltLevel']);
        $status = $filters['status'] ?? 'pending';
        $query->where('verification_status', $status);
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(fn ($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('whatsapp', 'like', "%$search%")
                ->orWhere(fn ($q) => $q->whereMemberNumber($search)));
        }
        $members = \App\Support\PageSize::paginate($query->latest('id'), $request, 15);
        $level = $review->level($request->user());
        return view('admin.verifications.index', compact('members', 'counts', 'status', 'level'));
    }

    public function show(Request $request, int $member, MembershipReview $review)
    {
        $member = $review->members($request->user())->with(['province', 'city', 'dojo', 'beltLevel'])->findOrFail($member);
        $fields = $review->data($member);
        $fingerprint = $review->fingerprint($member);
        $history = $member->membershipVerifications()->with('reviewer')->latest('id')->get();
        return view('admin.verifications.show', compact('member', 'fields', 'fingerprint', 'history'));
    }

    public function store(Request $request, int $member, MembershipReview $review)
    {
        abort_if($request->session()->has('impersonation'), 403, 'Kembali ke akun sendiri sebelum melakukan verifikasi.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'revision'])],
            'notes' => ['nullable', 'required_if:status,revision', 'string', 'max:5000'],
            'checked' => ['required_if:status,approved', 'array'],
            'checked.*' => ['string', 'distinct', Rule::in(array_merge(MembershipReview::FIELDS, ['belt_diploma']))],
            'fingerprint' => ['required', 'string', 'size:64'],
        ]);
        if ($data['status'] === 'approved' && array_diff(MembershipReview::FIELDS, $data['checked'] ?? [])) {
            throw ValidationException::withMessages(['checked' => 'Periksa dan centang seluruh data sebelum menyetujui.']);
        }
        DB::transaction(function () use ($request, $member, $review, $data) {
            $member = $review->members($request->user())->lockForUpdate()->findOrFail($member);
            if (!hash_equals($review->fingerprint($member), $data['fingerprint'])) {
                throw ValidationException::withMessages(['fingerprint' => 'Data anggota berubah. Muat ulang halaman dan periksa kembali.']);
            }
            if ($member->verification_status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pendaftaran ini sudah diperiksa. Muat ulang untuk melihat keputusan terbaru.']);
            }
            abort_if($member->id === $request->user()->id, 403);
            if ($data['status'] === 'approved' && $member->requested_belt_level_id) {
                if (!in_array('belt_diploma', $data['checked'] ?? []) || !$member->belt_diploma_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($member->belt_diploma_path)) {
                    throw ValidationException::withMessages(['checked' => 'Buka dan periksa ijazah sabuk, lalu centang bukti ijazah sebelum menyetujui.']);
                }
            }
            $member->membershipVerifications()->create([
                'reviewer_id' => $request->user()->id, 'reviewer_level' => $review->level($request->user()),
                'status' => $data['status'], 'notes' => $data['notes'] ?? null,
                'snapshot' => ['values' => $member->only(array_merge(MembershipReview::FIELDS, ['requested_belt_level_id', 'belt_diploma_path'])), 'fields' => $review->data($member), 'checked' => $data['checked'] ?? []],
            ]);
            $member->verification_status = $data['status'];
            if ($data['status'] === 'approved' && $member->requested_belt_level_id) {
                $member->belt_level_id = $member->requested_belt_level_id;
                $member->requested_belt_level_id = null;
            }
            $member->save();
            if ($member->verification_status !== $data['status']) $member->forceFill(['verification_status' => $data['status']])->saveQuietly();
            if ($data['status'] === 'approved') app(\App\Services\MemberNumbers::class)->confirmBlackBelt($member, $request->user());
        });
        return redirect()->route('admin.verifications.show', $member)->with('success', 'Keputusan verifikasi berhasil disimpan.');
    }
}
