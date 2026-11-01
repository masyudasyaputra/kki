<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamScoreController extends Controller
{
    /**
     * Tampilkan halaman penilaian (Scoring Grid)
     */
    public function index(Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorize(auth()->user(), $exam);
        // Eager load data pendaftaran (participants) dan sabuk
        $participants = app(\App\Services\ExamCertificates::class)->participants(auth()->user(), $exam)->with('currentBelt')->get();

        // Ambil skor yang sudah ada untuk ditampilkan awal, termasuk nama penguji
        $existingScores = ExamScore::where('exam_id', $exam->id)
            ->leftJoin('users', 'exam_scores.examiner_id', '=', 'users.id')
            ->select('exam_scores.*', 'users.name as examiner_name')
            ->get()
            ->keyBy('member_id');

        return view('admin.exams.scoring', compact('exam', 'participants', 'existingScores'));
    }

    /**
     * API untuk Fetch Data Scoring secara Real-time (Polling)
     * Menangani request dari JavaScript (fetchUpdates) agar data antar penguji sinkron
     */
    public function show(Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorize(auth()->user(), $exam);
        $scores = ExamScore::where('exam_id', $exam->id)
            ->leftJoin('users', 'exam_scores.examiner_id', '=', 'users.id')
            ->select('exam_scores.*', 'users.name as examiner_name')
            ->get()
            ->keyBy('member_id');

        return response()->json(['scores' => $scores]);
    }

    /**
     * Simpan nilai (Auto-Save via AJAX)
     * Menggunakan updateOrCreate untuk efisiensi dan mencegah error Integrity Constraint
     */
    public function store(Request $request, Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorize($request->user(), $exam);
        abort_if($exam->status === 'completed', 422, 'Ujian sudah difinalisasi.');
        $rules = ['scores' => ['required', 'array'], 'scores.*.result' => ['nullable', 'in:Lulus,Tidak Lulus,Percobaan'], 'scores.*.new_belt_level_id' => ['nullable', 'integer', 'exists:belt_levels,id']];
        foreach (\App\Services\ExamCertificates::MATERIALS as $field => $label) {
            $rules["scores.*.$field"] = ['nullable', function ($attribute, $value, $fail) use ($field) {
                if (in_array($field, ['kihon', 'kata', 'kumite']) && in_array($value, ['Kurang', 'Baik', 'Sangat Baik'], true)) return;
                if (!is_numeric($value) || $value < 0 || $value > 100) $fail('Nilai harus berupa angka 0 sampai 100.');
            }];
        }
        $request->validate($rules);
        $scores = $request->input('scores', []);
        $allowedMembers = app(\App\Services\ExamCertificates::class)->participants($request->user(), $exam)->pluck('user_id')->map(fn ($id) => (string) $id)->all();
        abort_if(array_diff(array_map('strval', array_keys($scores)), $allowedMembers), 403);

        foreach ($scores as $memberId => $data) {
            abort_unless(app(\App\Services\ExamCertificates::class)->participants($request->user(), $exam)->where('user_id', $memberId)->exists(), 403);
            if (!empty($data['new_belt_level_id'])) {
                abort_unless(\App\Models\BeltLevel::whereKey($data['new_belt_level_id'])->where('kyu_dan', 'like', '%'.$exam->exam_type.'%')->exists(), 422, 'Tingkat sabuk harus sesuai jenis ujian DAN / KYU.');
            }
            // Validasi: Hanya simpan jika minimal salah satu field penilaian sudah berinteraksi (tidak null)
            // Ini untuk mengatasi error "Column 'kihon' cannot be null" saat auto-save pertama kali
            if (collect($data)->only(array_keys(\App\Services\ExamCertificates::MATERIALS))->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty()) {

                ExamScore::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'member_id' => $memberId,
                    ],
                    [
                        ...collect($data)->only(['theory', 'paper', 'physical', 'self_defense', 'ippon_kumite'])->all(),
                        'examiner_id' => $request->user()->id,
                        'kihon' => $data['kihon'] ?? null,
                        'kata' => $data['kata'] ?? null,
                        'kumite' => $data['kumite'] ?? null,
                        'result' => $data['result'] ?? 'Lulus',
                        'new_belt_level_id' => $data['new_belt_level_id'] ?? null,
                    ]
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil disimpan otomatis'
        ]);
    }

    /**
     * Finalisasi hasil. Sabuk dan riwayat diperbarui saat sertifikat dikirim.
     */
    public function finalize(Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorizeSessionChange(auth()->user(), $exam);
        DB::beginTransaction();
        try {
            // 3. Update Status Ujian
            $exam->update(['status' => 'completed']);

            DB::commit();

            return redirect()->route('admin.exams.show', $exam)
                ->with('success', 'Hasil ujian difinalisasi. Kirim sertifikat melalui tab Pendaftaran untuk memperbarui tingkat sabuk anggota.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat finalisasi: ' . $e->getMessage());
        }
    }
}
