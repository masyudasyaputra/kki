<?php
namespace App\Services;

use App\Models\{Exam, ExamParticipant, ExamScore, User, CertificateTemplate};
use Illuminate\Validation\ValidationException;

class ExamCertificates
{
    public const MATERIALS = ['theory' => 'Pengetahuan umum / Karatedo', 'paper' => 'Karya tulis', 'physical' => 'Fisik', 'kihon' => 'Dasar / Kihon', 'kata' => 'Kata', 'self_defense' => 'Bela diri / Jujutsu', 'ippon_kumite' => 'Ippon Kumite', 'kumite' => 'Kumite'];

    /** Materi blanko UKT Kyu sesuai lembar kenaikan tingkat pada format rekap. */
    public const KYU_MATERIALS = [
        7 => [
            'theory' => ['TEORI / LISAN' => ['Sebutkan Pedoman KKI', 'KKI Singkatan dari Apa', 'Pengertian lambang 5 bunga sakura pada badges / simbol', 'Pengertian Karate-Do', 'Isi Pancasila', 'Arti penghormatan 3 kali sebelum dan sesudah latihan']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Shiko Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi'], 'DASAR BERGERAK' => ['Mae Geri Chudan Zenkutsu Dachi', 'Chudan Chuki Kiba Dachi', 'Shuto Uchi', 'Uraken Jodan (arah muka lawan)', 'Uraken Chudan (samping ulu hati)', 'Mae Geri Chudan', 'Yoko Geri (arah lutut)', 'Uraken Jodan (samping muka)', 'Ushiro Geri', 'Oitsuki Chudan', 'Oitsuki Jodan']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Shodan (Satu)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Dojo (Full Body Contact)']],
        ],
        6 => [
            'theory' => ['TEORI / LISAN' => ['Pengertian Karate-Do', 'Pengertian Karateka', 'Pengertian Karategi', 'FORKI singkatan dari apa', 'FORKI itu apa sebenarnya', 'Siapa yang membawa aliran Kushin Ryu ke Indonesia', 'Siapa nama Guru Besar KKI']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Kiba Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Geri Jodan', 'Shokuto Jodan', 'Giyaku Chuki Chudan']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Mae Geri + Chudan Chuki', 'Mae Geri + Oitsuki + Giyaku Chuki']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Shodan (Satu)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
        ],
        5 => [
            'theory' => ['TEORI / LISAN' => ['FORKI itu apa', 'Siapa Guru Besar KKI', 'Huruf M di antara Kushin Ryu dan Karatedo artinya apa']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Zenkutsu Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Jodan Geri', 'Shokuto (arah rusuk / Jodan)', 'Giyaku Chuki Chudan', 'Mae Geri + Oitsuki + Giyaku Chuki']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Nidan (Dua)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
        ],
        4 => [
            'theory' => ['TEORI / LISAN' => ['Siapa Guru Besar KKI', 'FORKI singkatan dari apa']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Zenkutsu Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Jodan Geri', 'Ushiro Mawashi Geri', 'Kijami Chuki + Giyaku Chuki', 'Chudan Uke (dari luar ke dalam)', 'Chudan Uke (dari dalam ke luar)', 'Jodan Uke']],
            'self_defense' => ['JUZITSU KUSHIN RYU / BELA DIRI' => ['Udenagashi', 'Udechikami']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Nidan (Dua)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
        ],
        3 => [
            'theory' => ['TEORI / LISAN' => ['Siapa Guru Besar KKI', 'FORKI singkatan dari apa']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Zenkutsu Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Jodan Geri', 'Ushiro Mawashi Geri', 'Kijami Chuki + Giyaku Chuki', 'Chudan Uke (dari luar ke dalam)', 'Chudan Uke (dari dalam ke luar)', 'Jodan Uke']],
            'self_defense' => ['JUZITSU KUSHIN RYU / BELA DIRI' => ['Udenagashi', 'Udechikami', 'Ire Jime', 'Kote Gaesi']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Sandan (Tiga)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
        ],
        2 => [
            'theory' => ['TEORI / LISAN' => ['Siapa Guru Besar KKI', 'FORKI singkatan dari apa', 'Teori perwasitan', 'Praktek perwasitan', 'Teori administrasi', 'Teori melatih', 'Praktek melatih']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Zenkutsu Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Jodan Geri', 'Ushiro Mawashi Geri', 'Kijami Chuki + Giyaku Chuki', 'Chudan Uke (dari luar ke dalam)', 'Chudan Uke (dari dalam ke luar)', 'Jodan Uke']],
            'self_defense' => ['JUZITSU KUSHIN RYU / BELA DIRI' => ['Udenagashi', 'Udechikami', 'Ire Jime', 'Kote Gaesi', 'Sawa I', 'Sawa II', 'Sawa III', 'Sawa IV']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Yondan (Empat)', 'Kata Pin An Godan (Lima)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
        ],
        1 => [
            'theory' => ['TEORI / LISAN' => ['Teori perwasitan', 'Praktek perwasitan', 'Teori administrasi', 'Teori melatih', 'Praktek melatih']],
            'kihon' => ['KIHON / DASAR DI TEMPAT' => ['Chudan Chuki Kiba Dachi', 'Chudan Chuki Naipanchin Dachi', 'Chudan Chuki Zenkutsu Dachi', 'Mae Geri Chudan Zenkutsu Dachi', 'Mawashi Geri Chudan (bola kaki)', 'Mawashi Jodan Geri', 'Ushiro Mawashi Geri', 'Kijami Chuki + Giyaku Chuki', 'Chudan Uke (dari luar ke dalam)', 'Chudan Uke (dari dalam ke luar)', 'Jodan Uke']],
            'self_defense' => ['JUZITSU KUSHIN RYU / BELA DIRI' => ['Udenagashi', 'Udechikami', 'Ire Jime', 'Kote Gaesi', 'Sawa I', 'Sawa II', 'Sawa III', 'Sawa IV']],
            'ippon_kumite' => ['IPPON KUMITE' => ['Menangkis + Menyerang']],
            'kata' => ['KATA PIN AN' => ['Kata Pin An Yondan (Empat)', 'Kata Pin An Godan (Lima)']],
            'kumite' => ['KUMITE / PERTARUNGAN BEBAS' => ['Pertandingan (Non Body Contact)']],
            'weapon' => ['PENGGUNAAN SENJATA' => ['Bojitsu / Tongkat', 'Trisula']],
        ],
    ];

    public static function kyuMaterialGroups(int $targetKyu): array
    {
        return self::KYU_MATERIALS[$targetKyu] ?? [];
    }

    public static function materials(string $type): array
    {
        return $type === 'dan' ? self::MATERIALS : array_diff_key(self::MATERIALS, array_flip(['paper', 'physical']));
    }

    public function authorize(User $actor, Exam $exam, bool $write = false): void
    {
        if (!$write && RoleAccess::nationalExamReader($actor)) return;
        if (!$write && $actor->hasRole('verifikator_ujian_pb') && $exam->reviewers()->where('users.id', $actor->id)->exists()) return;
        if ($actor->hasRole(['pb', 'super_admin'])) return;
        if ($actor->hasRole('panitia_ujian') && $exam->examiners()->where('users.id', $actor->id)->exists()) return;
        if (!$write && $actor->hasRole('admin_dojo') && $actor->dojo_id
            && $exam->participants()->where('dojo_id', $actor->dojo_id)->exists()) return;
        if ($actor->hasRole(['pengprov', 'admin_pengprov']) && $actor->province_id && (int) $exam->province_id === (int) $actor->province_id) return;
        if ($actor->hasRole(['pengcab', 'admin_pengcab']) && $actor->province_id && $actor->city_id
            && (int) $exam->province_id === (int) $actor->province_id
            && $exam->participants()->whereHas('user', fn ($q) => $q->where('city_id', $actor->city_id))->exists()) return;
        if (!$write && $actor->hasRole('penguji') && $exam->examiners()->where('users.id', $actor->id)->exists()) return;
        abort(403);
    }

    public function visibleExams(User $actor)
    {
        $query = Exam::query();
        if ($actor->hasRole('super_admin') || RoleAccess::nationalExamReader($actor)) return $query;
        return $query->where(function ($q) use ($actor) {
            $q->whereRaw('1 = 0');
            if ($actor->hasRole(['pengprov', 'admin_pengprov']) && $actor->province_id) $q->orWhere('province_id', $actor->province_id);
            if ($actor->hasRole(['pengcab', 'admin_pengcab']) && $actor->province_id && $actor->city_id) {
                $q->orWhere(fn ($r) => $r->where('province_id', $actor->province_id)->whereHas('participants.user', fn ($u) => $u->where('province_id', $actor->province_id)->where('city_id', $actor->city_id)));
            }
            if ($actor->hasRole('admin_dojo') && $actor->dojo_id) $q->orWhereHas('participants', fn ($p) => $p->where('dojo_id', $actor->dojo_id));
            if ($actor->hasRole(['panitia_ujian', 'penguji'])) $q->orWhereHas('examiners', fn ($u) => $u->where('users.id', $actor->id));
            if ($actor->hasRole('verifikator_ujian_pb')) $q->orWhereHas('reviewers', fn ($u) => $u->where('users.id', $actor->id));
        });
    }

    public function participants(User $actor, Exam $exam)
    {
        $query = $exam->participants()->with(['user.province', 'user.city', 'dojo', 'targetBelt']);
        $assigned = $actor->hasRole(['panitia_ujian', 'penguji']) && $exam->examiners()->where('users.id', $actor->id)->exists();
        $assigned = $assigned || ($actor->hasRole('verifikator_ujian_pb') && $exam->reviewers()->where('users.id', $actor->id)->exists());
        if (!$actor->hasRole('super_admin') && !RoleAccess::nationalExamReader($actor) && !RoleAccess::managesUsers($actor) && !$assigned) {
            if ($actor->hasRole(['pengcab', 'admin_pengcab'])) $query->whereHas('user', fn ($q) => $q->where('province_id', $actor->province_id ?? 0)->where('city_id', $actor->city_id ?? 0));
            elseif ($actor->hasRole(['pengprov', 'admin_pengprov'])) $query->whereHas('user', fn ($q) => $q->where('province_id', $actor->province_id ?? 0));
            elseif ($actor->hasRole('admin_dojo')) $query->where('dojo_id', $actor->dojo_id ?? 0);
        }
        return $query;
    }

    public function authorizeSessionChange(User $actor, Exam $exam): void
    {
        $this->authorize($actor, $exam, true);
        if (!$actor->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']) && !$actor->hasRole('panitia_ujian')) {
            abort_unless($this->participants($actor, $exam)->count() === $exam->participants()->count(), 403, 'Perubahan seluruh sesi hanya dapat dilakukan pengelola yang mencakup semua peserta.');
        }
    }

    public function templates(User $actor)
    {
        $this->authorizeTemplates($actor);
        return CertificateTemplate::query();
    }

    public function authorizeTemplates(User $actor): void
    {
        abort_unless(RoleAccess::certificates($actor), 403, 'Template dan sertifikat dikelola Pengurus Pusat.');
    }

    public function authorizeIssuance(User $actor, Exam $exam): void
    {
        $this->authorizeTemplates($actor);
    }

    public function canScore(User $actor, Exam $exam): bool
    {
        return $actor->hasRole(['penguji', 'panitia_ujian']) && $exam->examiners()->where('users.id', $actor->id)->exists();
    }

    public function authorizeCommittee(User $actor, Exam $exam): void
    {
        abort_unless($actor->hasRole('panitia_ujian') && $exam->examiners()->where('users.id', $actor->id)->exists(), 403);
    }

    public static function defaults(string $type): array
    {
        return ['heading' => 'PENGURUS PUSAT', 'organization' => 'KUSHIN RYU M KARATE-DO INDONESIA',
            'title' => 'IJAZAH', 'color' => $type === 'dan' ? '#9b773b' : '#ad342c',
            'signer_left_title' => 'Ketua Umum', 'signer_left_name' => '', 'signer_left_rank' => '',
            'signer_right_title' => 'Ketua Dewan Guru', 'signer_right_name' => '', 'signer_right_rank' => ''];
    }

    public function payload(Exam $exam, ExamParticipant $participant, CertificateTemplate $template, array $identity = [], bool $official = false): array
    {
        $templateSettings = array_merge(self::defaults($exam->exam_type), $template->settings ?? []);
        $assignedSigners = [];
        foreach (['left', 'right'] as $side) {
            $signerId = $templateSettings['signer_'.$side.'_user_id'] ?? null;
            $signer = $signerId ? User::with('beltLevel')->find($signerId) : null;
            $validSigner = $signer && $signer->is_active && str_contains(strtolower($signer->beltLevel?->kyu_dan ?? ''), 'dan');
            if ($validSigner) {
                $templateSettings['signer_'.$side.'_name'] = $signer->name;
                $templateSettings['signer_'.$side.'_rank'] = $signer->beltLevel->kyu_dan;
            } elseif ($official) {
                $assignedSigners[] = 'Pilih penandatangan '.$side.' yang berstatus aktif dan memiliki sabuk DAN.';
            }
        }
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
                $layoutKeys = array_column($template->settings['pdf_layout'] ?? [], 'key');
                $requiredLayoutKeys = ['name', 'no_id_msh', 'verification_qr', 'signer_left_title', 'signer_left_name', 'signer_left_rank', 'signer_right_title', 'signer_right_name', 'signer_right_rank'];
                if (empty($template->settings['pdf_path']) || array_diff($requiredLayoutKeys, $layoutKeys)) {
                    $errors[] = 'Atur koordinat PDF untuk nama, No. ID MSH KKI, QR verifikasi, dan jabatan/nama/tingkat DAN kedua penandatangan.';
                }
                if (in_array('no_id_msh', $layoutKeys, true) && empty($identity['no_id_msh'])) $errors[] = 'Nomor ID MSH KKI peserta belum diisi.';
            }
            $errors = array_merge($errors, $assignedSigners);
            if ($errors) throw ValidationException::withMessages(['certificate' => $errors]);
        }
        $settings = $exam->certificate_settings ?? [];
        return ['type' => $exam->exam_type, 'template' => array_merge($templateSettings, ['heading' => 'PENGURUS PUSAT']),
            'name' => $participant->user->name, 'member_number' => $participant->user->member_number,
            'no_id_msh' => $identity['no_id_msh'] ?? '',
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
