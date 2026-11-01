<?php
namespace App\Services;

use App\Models\{Exam, ExamParticipant, ExamScore, User, CertificateTemplate};
use Illuminate\Validation\ValidationException;

class ExamCertificates
{
    public const MATERIALS = ['theory' => 'Pengetahuan umum / Karatedo', 'paper' => 'Karya tulis', 'physical' => 'Fisik', 'kihon' => 'Dasar / Kihon', 'kata' => 'Kata', 'self_defense' => 'Bela diri / Jujutsu', 'ippon_kumite' => 'Ippon Kumite', 'kumite' => 'Kumite'];

    public static function materials(string $type): array
    {
        return $type === 'dan' ? self::MATERIALS : array_diff_key(self::MATERIALS, array_flip(['paper', 'physical']));
    }

    public function authorize(User $actor, Exam $exam, bool $write = false): void
    {
        if ($actor->hasRole('pb')) return;
        if ($actor->hasRole(['pengprov', 'admin_pengprov']) && $actor->province_id && (int) $exam->province_id === (int) $actor->province_id) return;
        if ($actor->hasRole(['pengcab', 'admin_pengcab']) && $actor->province_id && $actor->city_id
            && (int) $exam->province_id === (int) $actor->province_id
            && $exam->participants()->whereHas('user', fn ($q) => $q->where('city_id', $actor->city_id))->exists()) return;
        if (!$write && $actor->hasRole('penguji') && $exam->examiners()->where('users.id', $actor->id)->exists()) return;
        abort(403);
    }

    public function participants(User $actor, Exam $exam)
    {
        $query = $exam->participants()->with(['user.province', 'user.city', 'dojo', 'targetBelt']);
        if (!$actor->hasRole(['pb', 'pengprov', 'admin_pengprov']) && $actor->hasRole(['pengcab', 'admin_pengcab'])) {
            $query->whereHas('user', fn ($q) => $q->where('city_id', $actor->city_id));
        }
        return $query;
    }

    public function authorizeSessionChange(User $actor, Exam $exam): void
    {
        $this->authorize($actor, $exam, true);
        if (!$actor->hasRole(['pb', 'pengprov', 'admin_pengprov'])) {
            abort_unless($this->participants($actor, $exam)->count() === $exam->participants()->count(), 403, 'Perubahan seluruh sesi hanya dapat dilakukan pengelola yang mencakup semua peserta.');
        }
    }

    public function templates(User $actor)
    {
        $query = CertificateTemplate::query();
        if (!$actor->hasRole('pb')) {
            $query->where(fn ($q) => $q->whereNull('province_id')->orWhere('province_id', $actor->province_id))
                ->where(fn ($q) => $q->whereNull('city_id')->orWhere('city_id', $actor->city_id));
        }
        return $query;
    }

    public function authorizeTemplates(User $actor): void
    {
        if ($actor->hasRole('pb')) return;
        abort_unless($actor->province_id && ($actor->hasRole(['pengprov', 'admin_pengprov'])
            || ($actor->city_id && $actor->hasRole(['pengcab', 'admin_pengcab']))), 403);
    }

    public static function defaults(string $type): array
    {
        return ['heading' => $type === 'dan' ? 'PENGURUS PUSAT' : 'PENGURUS PROVINSI', 'organization' => 'KUSHIN RYU M KARATE-DO INDONESIA',
            'title' => 'IJAZAH', 'color' => $type === 'dan' ? '#9b773b' : '#ad342c',
            'signer_left_title' => 'Ketua Umum', 'signer_left_name' => '', 'signer_left_rank' => '',
            'signer_right_title' => 'Ketua Dewan Guru', 'signer_right_name' => '', 'signer_right_rank' => ''];
    }

    public function payload(Exam $exam, ExamParticipant $participant, CertificateTemplate $template, array $identity = [], bool $official = false): array
    {
        $score = ExamScore::with('newBeltLevel', 'examiner')->where('exam_id', $exam->id)->where('member_id', $participant->user_id)->first();
        $belt = $score?->newBeltLevel ?? $participant->targetBelt;
        $fields = self::materials($exam->exam_type);
        if ($official) {
            $errors = [];
            if ($exam->status !== 'completed') $errors[] = 'Finalisasi ujian sebelum menerbitkan sertifikat.';
            if (!$score || strtolower(trim($score->result)) !== 'lulus') $errors[] = 'Sertifikat hanya untuk peserta yang dinyatakan lulus oleh penguji/admin.';
            if (!$belt || !str_contains(strtolower($belt->kyu_dan), $exam->exam_type)) $errors[] = 'Jenis tingkat sabuk tidak sesuai dengan ujian DAN / KYU.';
            foreach ($fields as $key => $label) if ($score?->$key === null || $score?->$key === '') $errors[] = "Nilai $label belum diisi.";
            if (($template->settings['render_mode'] ?? '') === 'pdf') {
                if (empty($template->settings['pdf_path']) || empty($template->settings['pdf_layout']) || !in_array('name', array_column($template->settings['pdf_layout'] ?? [], 'key'))) $errors[] = 'Atur koordinat PDF, termasuk nama peserta, sebelum menerbitkan sertifikat.';
            } elseif (!$template->settings['signer_left_name'] || !$template->settings['signer_right_name']) $errors[] = 'Lengkapi nama penandatangan pada template.';
            if ($errors) throw ValidationException::withMessages(['certificate' => $errors]);
        }
        $settings = $exam->certificate_settings ?? [];
        return ['type' => $exam->exam_type, 'template' => array_merge(self::defaults($exam->exam_type), $template->settings),
            'name' => $participant->user->name, 'member_number' => $participant->user->member_number,
            'birth_place' => $identity['birth_place'] ?? '', 'birth_date' => $identity['birth_date'] ?? '',
            'province' => $participant->user->province?->name ?? '', 'city' => $participant->user->city?->name ?? '',
            'dojo' => $participant->dojo?->name ?? '', 'rank' => $belt?->kyu_dan ?? '', 'belt' => $belt?->name ?? '', 'belt_level_id' => $belt?->id,
            'exam_name' => $exam->name, 'exam_date' => $exam->execution_date->format('Y-m-d'), 'location' => $exam->location,
            'issue_place' => $settings['issue_place'] ?? $exam->location, 'issue_date' => $settings['issue_date'] ?? now()->toDateString(),
            'decision_number' => $settings['decision_number'] ?? '', 'decision_date' => $settings['decision_date'] ?? '',
            'examiner' => $score?->examiner?->name ?? '', 'result' => $score?->result ?? 'Belum dinilai',
            'scores' => collect($fields)->map(fn ($label, $key) => ['label' => $label, 'value' => $score?->$key, 'words' => self::words($score?->$key)])->all()];
    }

    public static function words($value): string
    {
        if ($value === null || $value === '') return '—';
        if (!is_numeric($value)) return (string) $value;
        $number = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        [$whole, $decimal] = array_pad(explode('.', $number), 2, null);
        $small = ['Nol', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $spell = function ($n) use (&$spell, $small): string {
            if ($n < 12) return $small[$n];
            if ($n < 20) return $spell($n - 10).' Belas';
            if ($n < 100) return $spell(intdiv($n, 10)).' Puluh'.($n % 10 ? ' '.$spell($n % 10) : '');
            return 'Seratus';
        };
        return $spell((int) $whole).($decimal !== null ? ' Koma '.implode(' ', array_map(fn ($n) => $small[(int) $n], str_split($decimal))) : '');
    }
}
