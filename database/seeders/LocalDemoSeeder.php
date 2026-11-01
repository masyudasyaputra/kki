<?php

namespace Database\Seeders;

use App\Models\{User, Province, City, Dojo, Official, BeltLevel, FeeConfiguration, ExamFee, Exam, ExamParticipant, ExamScore, ExamCertificate, Payment, MembershipVerification, CertificateTemplate, UserImpersonation};
use App\Services\{ExamCertificates, CertificateDelivery, MembershipReview};
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash, Storage};

/** Adds local test scenarios. Never truncates tables or resets existing demo edits. */
class LocalDemoSeeder extends Seeder
{
    public const PASSWORD = 'DemoKKI!2026';
    private string $passwordHash;
    private int $phone = 1;
    private int $memberNumber = 1;
    private CarbonImmutable $today;
    private array $members = [];
    private array $accounts = [];

    public function run(): void
    {
        $connection = DB::connection();
        if (!app()->environment('local') || !in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            || !in_array($connection->getConfig('host'), ['localhost', '127.0.0.1', '::1'], true)
            || $connection->getDatabaseName() !== 'kki') {
            throw new \RuntimeException('Seeder demo hanya diizinkan pada database kki di MySQL lokal, APP_ENV=local.');
        }
        $this->today = CarbonImmutable::today('Asia/Jakarta');
        $this->passwordHash = Hash::make(self::PASSWORD);
        $this->phone = 1; $this->memberNumber = 1; $this->members = []; $this->accounts = [];
        DB::transaction(function () {
            if (BeltLevel::count() === 0) $this->call(BeltLevelSeeder::class);
            $belts = BeltLevel::orderBy('order')->get();
            if ($belts->count() < 16) throw new \RuntimeException('Master tingkatan sabuk harus lengkap sebelum mengisi data demo.');
            foreach ($belts as $belt) ExamFee::firstOrCreate(['belt_level_id' => $belt->id], ['amount' => 100000 + $belt->order * 20000]);
            $pb = $this->account('pb', 'pb', 'Admin PB', [], ['pb', 'super_admin']);
            $nationalVerifier = $this->account('verifikator.pb', 'verifikator_pb', 'Tim Verifikasi PB');
            $this->account('verifikator.dewan-guru', 'verifikator_dewan_guru', 'Tim Verifikasi Dewan Guru');
            $templates = $this->templates();
            $regions = [
                'sumut' => ['SUMATERA UTARA', ['medan' => ['KOTA MEDAN', 'Medan Johor'], 'binjai' => ['KOTA BINJAI', 'Binjai Timur']]],
                'jabar' => ['JAWA BARAT', ['bandung' => ['KOTA BANDUNG', 'Coblong'], 'bogor' => ['KOTA BOGOR', 'Bogor Tengah']]],
                'jatim' => ['JAWA TIMUR', ['surabaya' => ['KOTA SURABAYA', 'Wonokromo'], 'malang' => ['KOTA MALANG', 'Klojen']]],
            ];
            foreach ($regions as $slug => [$provinceName, $cities]) {
                $province = Province::firstOrCreate(['name' => $provinceName]);
                if (!$province->sk_number && !$province->leader_name) $province->update(['leader_name' => '[DEMO] Ketua '.$provinceName,
                    'sk_number' => 'DEMO/SK/PROVINSI/'.strtoupper($slug), 'sk_expiry_date' => $this->today->addDays($slug === 'jatim' ? -25 : ($slug === 'jabar' ? 20 : 300))->toDateString()]);
                $admin = $this->account('pengprov.'.$slug, 'pengprov', 'Pengprov '.$provinceName, ['province_id' => $province->id]);
                $this->account('admin-pengprov.'.$slug, 'admin_pengprov', 'Admin Pengprov '.$provinceName, ['province_id' => $province->id]);
                $verifier = $this->account('verifikator.'.$slug, 'verifikator_pengprov', 'Tim Verifikasi '.$provinceName, ['province_id' => $province->id]);
                $examiner = $this->account('penguji.'.$slug, 'penguji', 'Penguji '.$provinceName, ['province_id' => $province->id]);
                $this->official('DEMO/SK/PROV/'.$slug, 'Ketua Pengprov '.$provinceName, 'pengprov', $province->id);
                foreach ($belts as $belt) FeeConfiguration::firstOrCreate(['province_id' => $province->id, 'belt_level_id' => $belt->id], ['amount' => $belt->membership_fee + ($slug === 'jabar' ? 10000 : 0)]);
                $groups = [];
                foreach ($cities as $citySlug => [$cityName, $district]) {
                    $city = City::firstOrCreate(['province_id' => $province->id, 'name' => $cityName]);
                    $scope = ['province_id' => $province->id, 'city_id' => $city->id];
                    $this->account('pengcab.'.$citySlug, 'pengcab', 'Pengcab '.$cityName, $scope);
                    $this->account('admin-pengcab.'.$citySlug, 'admin_pengcab', 'Admin Pengcab '.$cityName, $scope);
                    $this->account('verifikator.'.$citySlug, 'verifikator_pengcab', 'Tim Verifikasi '.$cityName, $scope);
                    $this->official('DEMO/SK/CAB/'.$citySlug, 'Ketua Pengcab '.$cityName, 'pengcab', $province->id, $city->id, count($groups) ? 20 : 300);
                    for ($dojoIndex = 1; $dojoIndex <= 2; $dojoIndex++) {
                        $expiry = ($citySlug === 'binjai' || $citySlug === 'malang') && $dojoIndex === 2 ? -20 : ($dojoIndex === 2 ? 20 : 300);
                        $dojo = Dojo::firstOrCreate(['sk_number' => 'DEMO/SK/DOJO/'.$citySlug.'/'.$dojoIndex], [
                            'name' => '[DEMO] Dojo '.ucfirst($citySlug).' '.$dojoIndex, 'province_id' => $province->id, 'city_id' => $city->id,
                            'address' => 'Alamat uji coba, Jl. Latihan No. '.$dojoIndex.', '.$district,
                            'sensei_name' => '[DEMO] Sensei '.ucfirst($citySlug).' '.$dojoIndex, 'phone_number' => $this->nextPhone(),
                            'sk_expiry_date' => $this->today->addDays($expiry)->toDateString(),
                        ]);
                        $dojoAdmin = $this->account('dojo.'.$citySlug.$dojoIndex, 'admin_dojo', 'Admin Dojo '.ucfirst($citySlug).' '.$dojoIndex, $scope + ['dojo_id' => $dojo->id]);
                        $group = ['dojo' => $dojo, 'admin' => $dojoAdmin, 'members' => []];
                        $names = ['Andi Pratama', 'Bunga Lestari', 'Citra Maharani', 'Dimas Saputra', 'Eka Wijaya', 'Farah Aulia', 'Gilang Ramadhan', 'Hana Putri'];
                        $orders = [1, 6, 9, 10, 11, 5, 8, 2];
                        foreach ($names as $slot => $name) {
                            $number = $this->memberNumber++;
                            $member = $this->account('anggota'.str_pad($number, 3, '0', STR_PAD_LEFT), 'member', $name.' '.ucfirst($citySlug).' '.$dojoIndex,
                                $scope + ['dojo_id' => $dojo->id, 'district' => $district, 'parent_name' => '[DEMO] Orang Tua '.$name,
                                    'belt_level_id' => $belts->firstWhere('order', $orders[$slot])->id, 'is_active' => !in_array($slot, [0, 2, 7]),
                                    'expired_at' => $this->today->addMonths(in_array($slot, [2, 7]) ? -1 : 11)]);
                            $group['members'][$slot] = $member;
                            $this->members[] = ['user' => $member, 'slot' => $slot, 'verifier' => $slot % 2 ? $verifier : $nationalVerifier];
                        }
                        $groups[] = $group;
                    }
                }
                $this->exams($slug, $province, $groups, $examiner, $pb, $templates, $belts);
            }
            $this->official('DEMO/SK/PB', 'Ketua Pengurus Pusat', 'pb');
            $this->official('DEMO/SK/EXPIRED', 'Pengurus Masa SK Berakhir', 'pb', null, null, -30);
            $this->verificationAndMembershipPayments();
            $firstMember = $this->members[0]['user'];
            UserImpersonation::firstOrCreate(['actor_id' => $pb->id, 'target_id' => $firstMember->id, 'started_at' => $this->today->subDay()->setTime(9, 0)],
                ['actor_name' => $pb->name, 'target_name' => $firstMember->name, 'ended_at' => $this->today->subDay()->setTime(9, 5), 'end_reason' => 'returned']);
        });
        $this->writeGuide();
        $this->command?->info('Data demo lokal selesai. Akun asli tetap dipertahankan. Password akun demo: '.self::PASSWORD);
        $this->command?->info('Panduan: docs/LOCAL_DEMO_DATA.md | Akun lengkap: storage/app/demo/akun-uji.csv');
    }

    private function nextPhone(): string
    {
        return '089900'.str_pad($this->phone++, 7, '0', STR_PAD_LEFT);
    }

    private function account(string $login, string $role, string $name, array $attributes = [], ?array $roles = null): User
    {
        $user = User::firstOrCreate(['email' => $login.'@demo.kki.test'], array_replace([
            'name' => '[DEMO] '.$name, 'password' => $this->passwordHash, 'role' => $role, 'roles' => $roles ?? [$role],
            'is_active' => true, 'whatsapp' => $this->nextPhone(),
        ], $attributes));
        if (!str_starts_with($user->name, '[DEMO]')) throw new \RuntimeException('Alamat akun demo sudah dipakai data lain: '.$user->email);
        if ($user->wasRecentlyCreated) $user->forceFill(['email_verified_at' => now()])->save();
        $this->accounts[$user->email] = $user;
        return $user;
    }

    private function official(string $sk, string $name, string $level, ?int $province = null, ?int $city = null, int $expiry = 300): void
    {
        Official::firstOrCreate(['sk_number' => $sk], ['name' => '[DEMO] '.$name, 'position' => 'Ketua', 'level' => $level,
            'province_id' => $province, 'city_id' => $city, 'phone_number' => $this->nextPhone(), 'sk_expiry_date' => $this->today->addDays($expiry)->toDateString()]);
    }

    private function templates(): array
    {
        $templates = [];
        foreach (['dan', 'kyu'] as $type) {
            $settings = array_replace(ExamCertificates::defaults($type), ['signer_left_name' => '[DEMO] Ketua Umum KKI', 'signer_right_name' => '[DEMO] Ketua Dewan Guru', 'signer_left_rank' => 'DATA UJI COBA', 'signer_right_rank' => 'DATA UJI COBA']);
            CertificateTemplate::firstOrCreate(['name' => '[DEMO] '.strtoupper($type).' - Desain Bawaan'], ['exam_type' => $type, 'settings' => $settings]);
            $path = 'certificates/demo/desain-'.$type.'.pdf';
            if (!Storage::disk('public')->exists($path)) Storage::disk('public')->put($path, $this->demoPdf($type));
            $layout = [];
            foreach (['number' => [180, 18, 95, 10], 'name' => [65, 73, 165, 21], 'birth_details' => [80, 92, 180, 12],
                'province' => [80, 105, 180, 12], 'rank' => [85, 126, 130, 24], 'belt' => [85, 140, 130, 12],
                'issue_place' => [80, 154, 65, 11], 'issue_date' => [152, 154, 90, 11]] as $key => [$x, $y, $width, $size]) $layout[] = $this->field($key, 1, $x, $y, $width, $size, in_array($key, ['name', 'rank', 'belt']) ? 'center' : 'left');
            $layout[] = $this->field('name', 2, 45, 30, 160, 14);
            $layout[] = $this->field('exam_date', 2, 45, 41, 110, 11);
            $layout[] = $this->field('location', 2, 175, 41, 100, 11);
            foreach (array_keys(ExamCertificates::materials($type)) as $index => $key) {
                $layout[] = $this->field('score_'.$key, 2, 145, 72 + $index * 10, 28, 12, 'center');
                $layout[] = $this->field('words_'.$key, 2, 185, 72 + $index * 10, 90, 12);
            }
            $layout[] = $this->field('examiner', 2, 55, 175, 190, 12);
            $settings += ['render_mode' => 'pdf', 'pdf_path' => $path, 'pdf_layout' => $layout, 'pdf_pages' => [['width' => 297, 'height' => 210], ['width' => 297, 'height' => 210]]];
            $templates[$type] = CertificateTemplate::firstOrCreate(['name' => '[DEMO] '.strtoupper($type).' - PDF Koordinat'], ['exam_type' => $type, 'settings' => $settings]);
        }
        return $templates;
    }

    private function field(string $key, int $page, float $x, float $y, float $width, float $size, string $align = 'left'): array
    {
        return ['key' => $key, 'page' => $page, 'x' => $x, 'y' => $y, 'width' => $width, 'font_size' => $size, 'line_height' => $size * 1.2,
            'wrap' => false, 'align' => $align, 'font' => 'serif', 'bold' => in_array($key, ['name', 'rank']), 'color' => '#111827'];
    }

    private function exams(string $slug, Province $province, array $groups, User $examiner, User $pb, array $templates, $belts): void
    {
        // Completed sessions first: future sessions capture the member's updated belt.
        $scenarios = [
            ['Selesai KYU - Siap Kirim', 'kyu', 'completed', -12, [1, 6]],
            ['Selesai DAN - Siap Kirim', 'dan', 'completed', -5, [3, 4]],
            ['Pendaftaran KYU', 'kyu', 'open', 14, [0, 2]],
            ['Pendaftaran DAN', 'dan', 'open', 21, [3, 4]],
            ['Penilaian KYU Berlangsung', 'kyu', 'ongoing', 0, [5]],
            ['Draf KYU', 'kyu', 'draft', 30, [7]],
        ];
        foreach ($scenarios as [$label, $type, $status, $days, $slots]) {
            $exam = Exam::firstOrCreate(['name' => '[DEMO] '.$label.' '.strtoupper($slug)], ['exam_type' => $type,
                'execution_date' => $this->today->addDays($days)->toDateString(), 'location' => '[DEMO] Gedung Latihan '.$province->name,
                'province_id' => $province->id, 'status' => $status,
                'certificate_settings' => ['template_id' => $templates[$type]->id, 'issue_place' => $province->name, 'issue_date' => $this->today->toDateString(),
                    'decision_number' => 'DEMO/SK/UJIAN/'.strtoupper($slug).'/'.strtoupper($type), 'decision_date' => $this->today->subDays(15)->toDateString()]]);
            $exam->examiners()->syncWithoutDetaching([$examiner->id]);
            foreach ($groups as $groupIndex => $group) {
                $registered = [];
                foreach ($slots as $slot) {
                    $member = $group['members'][$slot]->fresh();
                    $current = $belts->firstWhere('id', $member->belt_level_id);
                    $targetOrder = $type === 'dan' ? min(16, max(11, $current->order + 1)) : min(10, $current->order + 1);
                    $target = $belts->firstWhere('order', $targetOrder);
                    $participant = ExamParticipant::firstOrCreate(['exam_id' => $exam->id, 'user_id' => $member->id], [
                        'dojo_id' => $group['dojo']->id, 'current_belt_id' => $current->id, 'target_belt_id' => $target->id,
                        'fee_amount' => 100000 + $targetOrder * 20000, 'payment_status' => $status === 'completed' || $groupIndex % 2 === 0 ? 'paid' : 'unpaid',
                        'result' => 'pending',
                    ]);
                    $isNew = $participant->wasRecentlyCreated;
                    if ($isNew) {
                        $participant->certificate_identity = ['birth_place' => $member->city?->name ?? 'Medan', 'birth_date' => '200'.($slot % 8).'-0'.(($slot % 8) + 1).'-15'];
                        $participant->save();
                    }
                    if (in_array($status, ['completed', 'ongoing'])) {
                        $result = $status === 'ongoing' ? ($groupIndex % 2 ? 'Percobaan' : 'Lulus') : ($groupIndex === 3 && $slot === $slots[0] ? 'Tidak Lulus' : 'Lulus');
                        $values = array_fill_keys(array_keys(ExamCertificates::materials($type)), $result === 'Tidak Lulus' ? 55 : 70 + $groupIndex * 5);
                        if ($status === 'ongoing') { $values['theory'] = null; $values['ippon_kumite'] = null; }
                        $score = ExamScore::firstOrCreate(['exam_id' => $exam->id, 'member_id' => $member->id], $values + ['examiner_id' => $examiner->id, 'result' => $result, 'new_belt_level_id' => $target->id]);
                        if ($status === 'completed' && $isNew && $score->result === 'Lulus' && $slot === $slots[0] && $groupIndex < 3) {
                            app(CertificateDelivery::class)->send($pb, $exam, $participant->id);
                        } elseif ($status === 'completed' && $isNew && $score->result === 'Tidak Lulus') {
                            $participant->result = 'failed'; $participant->save();
                        }
                        if ($status === 'completed' && $score->result === 'Lulus' && $groupIndex === 0 && $slot === $slots[1]) {
                            $number = 'KKI/'.strtoupper($type).'/'.$exam->id.'/'.str_pad($participant->id, 6, '0', STR_PAD_LEFT).'/'.$exam->execution_date->format('Y');
                            $snapshot = app(ExamCertificates::class)->payload($exam, $participant, $templates[$type], $participant->certificate_identity ?? [], true);
                            $snapshot['number'] = $number;
                            ExamCertificate::firstOrCreate(['exam_participant_id' => $participant->id], ['issued_by' => $pb->id, 'number' => $number, 'snapshot' => $snapshot]);
                        }
                    }
                    $registered[] = $participant;
                }
                $paid = $status === 'completed' || $groupIndex % 2 === 0;
                $this->payment('DEMO-EXAM-'.$exam->id.'-'.$group['dojo']->id, $group['admin'], 'exam_fee', $paid ? 'paid' : 'pending',
                    collect($registered)->sum('fee_amount'), null, ['exam_id' => $exam->id, 'participant_ids' => collect($registered)->pluck('id')->all()]);
            }
        }
    }

    private function verificationAndMembershipPayments(): void
    {
        foreach ($this->members as $data) {
            $member = $data['user']->fresh(); $slot = $data['slot']; $verifier = $data['verifier'];
            $status = in_array($slot, [0, 5, 7]) ? 'pending' : ($slot === 2 ? 'revision' : 'approved');
            if ($status !== 'pending') {
                $entry = MembershipVerification::firstOrCreate(['member_id' => $member->id], ['reviewer_id' => $verifier->id,
                    'reviewer_level' => app(MembershipReview::class)->level($verifier), 'status' => $status,
                    'notes' => $status === 'revision' ? '[DEMO] Periksa kembali kecamatan dan nama orang tua.' : '[DEMO] Data pendaftaran lengkap dan sesuai.',
                    'snapshot' => ['values' => $member->only(MembershipReview::FIELDS), 'fields' => app(MembershipReview::class)->data($member), 'checked' => MembershipReview::FIELDS]]);
                if ($entry->wasRecentlyCreated) { $member->verification_status = $status; $member->save(); }
            }
            $paymentStatus = $slot === 7 ? 'failed' : (in_array($slot, [0, 2]) ? 'pending' : 'paid');
            $this->payment('DEMO-IURAN-'.$member->id, $member, 'membership_fee', $paymentStatus,
                (int) ($member->beltLevel?->membership_fee ?? 75000), $member->belt_level_id, ['scenario' => 'Iuran anggota dummy']);
        }
    }

    private function payment(string $invoice, User $payer, string $type, string $status, int $amount, ?int $belt, array $meta): void
    {
        $payment = Payment::firstOrCreate(['invoice_number' => $invoice], ['user_id' => $payer->id, 'type' => $type,
            'reference' => $type === 'exam_fee' ? 'DEMO:EXAM:'.($meta['exam_id'] ?? '') : 'DEMO:IURAN:'.$payer->id,
            'amount' => $amount, 'belt_level_id' => $belt, 'status' => $status, 'payment_url' => null,
            'paid_at' => $status === 'paid' ? $this->today->subDays(2)->setTime(10, 0) : null,
            'expires_at' => $status === 'pending' ? $this->today->addDays(7) : null,
            'payload' => ['dummy' => true, 'notice' => 'Simulasi lokal; tidak ada transaksi DOKU.']]);
        if ($payment->wasRecentlyCreated) { $payment->meta = ['dummy' => true] + $meta; $payment->save(); }
    }

    private function demoPdf(string $type): string
    {
        $pt = fn (float $mm) => round($mm * 72 / 25.4, 2);
        $text = function (string $value, float $x, float $y, int $size = 12) use ($pt): string {
            $value = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
            return 'BT /F1 '.$size.' Tf 0.1 0.1 0.1 rg '.$pt($x).' '.$pt(210 - $y).' Td ('.$value.") Tj ET\n";
        };
        $color = $type === 'dan' ? '.62 .45 .16' : '.7 .12 .15';
        $base = "$color RG 3 w 22 22 798 551 re S\n";
        $front = $base.$text('CONTOH / DATA DUMMY - BUKAN SERTIFIKAT RESMI', 60, 28, 12)
            .$text('IJAZAH '.strtoupper($type).' - KKI', 90, 48, 26).$text('KUSHIN RYU M KARATE-DO INDONESIA', 78, 58, 15)
            .$text('Nama', 25, 80).$text('Tempat / Tanggal Lahir', 25, 96).$text('Provinsi', 25, 109)
            .$text('Telah berhak menyandang tingkat', 102, 119).$text('Dikeluarkan di', 48, 158)
            .$text('[DEMO] Ketua Umum', 35, 184).$text('[DEMO] Ketua Dewan Guru', 190, 184);
        $back = $base.$text('LEMBAR NILAI - DATA UJI COBA', 83, 18, 20).$text('Nama:', 22, 34).$text('Tanggal ujian:', 22, 45).$text('Lokasi:', 155, 45)
            .$text('MATERI', 30, 63, 14).$text('NILAI', 147, 63, 14).$text('TERBILANG', 188, 63, 14);
        foreach (array_values(ExamCertificates::materials($type)) as $index => $label) $back .= $text($label, 25, 76 + $index * 10);
        $back .= $text('Penguji:', 25, 179).$text('Sertifikat ini khusus demonstrasi aplikasi lokal.', 70, 198, 10);
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R 5 0 R] /Count 2 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] /Resources << /Font << /F1 7 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($front).">>\nstream\n".$front.'endstream',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] /Resources << /Font << /F1 7 0 R >> >> /Contents 6 0 R >>',
            '<< /Length '.strlen($back).">>\nstream\n".$back.'endstream', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
        $pdf = "%PDF-1.4\n"; $offsets = [];
        foreach ($objects as $index => $object) { $offsets[] = strlen($pdf); $pdf .= ($index + 1)." 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 8\n0000000000 65535 f \n";
        foreach ($offsets as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer\n<< /Size 8 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private function writeGuide(): void
    {
        $directory = storage_path('app/demo');
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $handle = fopen($directory.'/akun-uji.csv', 'w');
        fputcsv($handle, ['Email', 'Password awal', 'Role', 'Nama', 'WhatsApp'], ',', '"', '');
        foreach ($this->accounts as $account) fputcsv($handle, [$account->email, self::PASSWORD, $account->role, $account->name, $account->whatsapp], ',', '"', '');
        fclose($handle);
    }
}
