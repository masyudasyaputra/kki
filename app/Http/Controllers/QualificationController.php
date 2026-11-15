<?php

namespace App\Http\Controllers;

use App\Models\{OfficialQualification, User};
use App\Services\QualificationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QualificationController extends Controller
{
    private function eligible(User $user): void
    {
        abort_if($user->registration_type === 'official' || (!$user->hasRole('member') && !$user->belt_level_id), 403, 'Pengajuan kompetensi tersedia untuk karateka.');
    }

    public function index(Request $request)
    {
        $this->eligible($request->user());
        $qualifications = $request->user()->qualifications()->latest('id')->paginate(10);
        return view('member.qualifications', compact('qualifications'));
    }

    public function store(Request $request)
    {
        $this->eligible($request->user());
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(OfficialQualification::KINDS))],
            'license_level' => ['required', 'string', 'max:150'],
            'certificate_number' => ['required', 'string', 'max:150'],
            'issuer' => ['required', 'string', 'max:255'],
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at', 'after_or_equal:today'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $path = $request->file('document')->store('qualifications/'.$request->user()->id, 'local');
        try {
            DB::transaction(function () use ($request, $data, $path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $this->eligible($user);
                if (!$user->province_id || !$user->city_id) throw ValidationException::withMessages(['kind' => 'Lengkapi provinsi dan kabupaten / kota di profil sebelum mengajukan.']);
                if ($user->qualifications()->where('status', 'pending')->exists()) throw ValidationException::withMessages(['kind' => 'Masih ada pengajuan menunggu pemeriksaan. Tunggu keputusan sebelum mengajukan lagi.']);
                $user->qualifications()->create(collect($data)->except('document')->all() + [
                    'document_path' => $path, 'province_id' => $user->province_id, 'city_id' => $user->city_id, 'status' => 'pending',
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        return redirect()->route('qualifications.index')->with('success', 'Pengajuan Wasit/Juri dikirim untuk diperiksa.');
    }

    public function document(Request $request, OfficialQualification $qualification, QualificationReview $review)
    {
        if ($request->user()->id !== $qualification->user_id) {
            abort_unless($review->applications($request->user())->whereKey($qualification->id)->exists(), 403);
        }
        abort_unless(Storage::disk('local')->exists($qualification->document_path), 404);
        return response()->file(Storage::disk('local')->path($qualification->document_path), ['X-Content-Type-Options' => 'nosniff']);
    }
}
