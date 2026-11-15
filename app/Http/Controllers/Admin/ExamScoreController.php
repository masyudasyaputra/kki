<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamScore;
use Illuminate\Http\Request;

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
        $existingScores = ExamScore::where('exam_id', $exam->id)->whereIn('member_id', app(\App\Services\ExamCertificates::class)->participants(auth()->user(), $exam)->pluck('user_id'))
            ->leftJoin('users', 'exam_scores.examiner_id', '=', 'users.id')
            ->select('exam_scores.*', 'users.name as examiner_name')
            ->get()
            ->keyBy('member_id');

        return view('admin.exams.scoring', compact('exam', 'participants', 'existingScores'));
    }

    /** Rekap peserta dan status UKT untuk dicetak/diekspor sebagai CSV Excel. */
    public function roster(Request $request, Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorize($request->user(), $exam);
        $participants = app(\App\Services\ExamCertificates::class)->participants($request->user(), $exam)
            ->with(['user.province', 'user.city', 'dojo', 'currentBelt', 'targetBelt'])->get();
        $scores = ExamScore::with('newBeltLevel')->where('exam_id', $exam->id)->whereIn('member_id', $participants->pluck('user_id'))->get()->keyBy('member_id');
        if ($request->query('format') === 'csv') {
            $filename = 'rekap-ukt-'.str($exam->name)->slug().'.csv';
            return response()->streamDownload(function () use ($participants, $scores) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['No', 'No. Anggota', 'Nama', 'Tempat/Tanggal Lahir', 'Dojo', 'Cabang/Kota', 'Provinsi', 'Tingkat Saat Ini', 'Naik ke Tingkat', 'No. Ujian', 'Nilai', 'Hasil', 'Status UKT', 'Biaya']);
                foreach ($participants as $i => $p) {
                    $score = $scores[$p->user_id] ?? null;
                    fputcsv($out, [$i + 1, $p->user->member_number, $p->user->name, trim(($p->certificate_identity['birth_place'] ?? '').' / '.($p->certificate_identity['birth_date'] ?? '')), $p->dojo?->name, $p->user->city?->name, $p->user->province?->name, $p->currentBelt?->kyu_dan, $score?->newBeltLevel?->kyu_dan ?? $p->targetBelt?->kyu_dan, $p->id, $score ? collect(['theory', 'kihon', 'ippon_kumite', 'kata', 'self_defense', 'kumite'])->map(fn($key) => $score->$key)->filter(fn($value) => $value !== null && $value !== '')->implode(' / ') : '', $score?->result ?? 'Belum dinilai', $p->payment_status === 'paid' ? 'Lunas' : 'Belum lunas', $p->fee_amount]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('admin.exams.roster', compact('exam', 'participants', 'scores'));
    }

    /** Blanko penilaian khusus tingkat peserta sesuai lembar Kyu di workbook. */
    public function blanko(Request $request, Exam $exam, \App\Models\ExamParticipant $participant)
    {
        app(\App\Services\ExamCertificates::class)->authorize($request->user(), $exam);
        abort_unless((int) $participant->exam_id === (int) $exam->id && app(\App\Services\ExamCertificates::class)->participants($request->user(), $exam)->whereKey($participant->id)->exists(), 404);
        $participant->load(['user.province', 'user.city', 'dojo', 'currentBelt', 'targetBelt']);
        $identity = $participant->certificate_identity ?? [];
        $target = $participant->targetBelt;
        preg_match('/kyu\\s*(\\d+)/i', $target?->kyu_dan ?? '', $matches);
        $targetKyu = (int) ($matches[1] ?? 0);
        $groups = $exam->exam_type === 'kyu' ? \App\Services\ExamCertificates::kyuMaterialGroups($targetKyu) : [];
        $score = ExamScore::with(['examiner', 'newBeltLevel'])->where('exam_id', $exam->id)->where('member_id', $participant->user_id)->first();
        return view('admin.exams.blanko', compact('exam', 'participant', 'groups', 'score', 'targetKyu', 'identity'));
    }

    /**
     * API untuk Fetch Data Scoring secara Real-time (Polling)
     * Menangani request dari JavaScript (fetchUpdates) agar data antar penguji sinkron
     */
    public function show(Exam $exam)
    {
        app(\App\Services\ExamCertificates::class)->authorize(auth()->user(), $exam);
        $scores = ExamScore::where('exam_id', $exam->id)->whereIn('member_id', app(\App\Services\ExamCertificates::class)->participants(auth()->user(), $exam)->pluck('user_id'))
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
        abort_unless(app(\App\Services\ExamCertificates::class)->canScore($request->user(), $exam), 403);
        app(\App\Services\ExamReviewWorkflow::class)->assertEditable($exam);
        $rules = ['scores' => ['required', 'array'], 'scores.*.result' => ['nullable', 'in:Lulus,Tidak Lulus,Percobaan'], 'scores.*.new_belt_level_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('belt_levels', 'id')->where('is_active', true)], 'scores.*.material_labels' => ['nullable', 'array'], 'scores.*.material_labels.*' => ['string', 'max:255'], 'scores.*.material_scores' => ['nullable', 'array'], 'scores.*.material_scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'], 'scores.*.material_scores.*.note' => ['nullable', 'string', 'max:255'], 'scores.*.notes' => ['nullable', 'string', 'max:1000']];
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
                abort_unless(\App\Models\BeltLevel::active()->whereKey($data['new_belt_level_id'])->where('kyu_dan', 'like', '%'.$exam->exam_type.'%')->exists(), 422, 'Tingkat sabuk harus sesuai jenis ujian DAN / KYU.');
            }
            // Validasi: Hanya simpan jika minimal salah satu field penilaian sudah berinteraksi (tidak null)
            // Ini untuk mengatasi error "Column 'kihon' cannot be null" saat auto-save pertama kali
            if (!empty($data['material_labels'])) {
                foreach ($data['material_scores'] ?? [] as $index => $item) {
                    $data['material_scores'][$index]['label'] = $data['material_labels'][$index] ?? '';
                }
            }
            if (collect($data)->only(array_keys(\App\Services\ExamCertificates::MATERIALS))->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty() || !empty($data['material_scores']) || !empty($data['result'])) {

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
                        'material_scores' => collect($data['material_scores'] ?? [])->map(fn ($item) => is_array($item) ? $item : [])->all() ?: null,
                        'notes' => $data['notes'] ?? null,
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
     * Kirim data dan nilai untuk diverifikasi pusat sebelum sesi diselesaikan.
     */
    public function finalize(Exam $exam)
    {
        app(\App\Services\ExamReviewWorkflow::class)->submit(auth()->user(), $exam);
        return redirect()->route('admin.exam-reviews.show', $exam)
            ->with('success', 'Data peserta dan nilai dikirim untuk pengesahan '.app(\App\Services\ExamAuthority::class)->destination($exam).'.');
    }
}
