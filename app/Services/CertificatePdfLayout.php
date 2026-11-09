<?php

namespace App\Services;

class CertificatePdfLayout
{
    public static function fields(): array
    {
        $fields = ['name' => 'Nama peserta', 'number' => 'Nomor sertifikat', 'member_number' => 'Nomor anggota KKI', 'no_id_msh' => 'No. ID MSH KKI',
            'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'birth_details' => 'Tempat / tanggal lahir',
            'province' => 'Provinsi', 'city' => 'Kabupaten / Kota', 'dojo' => 'Dojo / Ranting',
            'rank' => 'Tingkat DAN / KYU', 'belt' => 'Warna sabuk', 'exam_name' => 'Nama ujian',
            'exam_date' => 'Tanggal ujian', 'location' => 'Lokasi ujian', 'issue_place' => 'Tempat penerbitan',
            'issue_date' => 'Tanggal penerbitan', 'decision_number' => 'Nomor SK', 'decision_date' => 'Tanggal SK',
            'examiner' => 'Nama penguji', 'result' => 'Hasil ujian',
            'signer_left_title' => 'Jabatan penandatangan kiri', 'signer_left_name' => 'Nama penandatangan kiri', 'signer_left_rank' => 'Tingkat DAN penandatangan kiri',
            'signer_right_title' => 'Jabatan penandatangan kanan', 'signer_right_name' => 'Nama penandatangan kanan', 'signer_right_rank' => 'Tingkat DAN penandatangan kanan',
            'verification_qr' => 'QR TTE / verifikasi sertifikat', 'custom' => 'Teks bebas'];
        foreach (ExamCertificates::MATERIALS as $key => $label) {
            $fields['score_'.$key] = 'Nilai: '.$label;
            $fields['words_'.$key] = 'Terbilang: '.$label;
        }
        return $fields;
    }

    public static function values(array $data): array
    {
        $values = [];
        foreach (self::fields() as $key => $label) $values[$key] = (string) ($data[$key] ?? '');
        foreach (['birth_date', 'exam_date', 'issue_date', 'decision_date'] as $key) {
            $values[$key] = !empty($data[$key]) ? \Carbon\Carbon::parse($data[$key])->locale('id')->translatedFormat('d F Y') : '';
        }
        $values['birth_details'] = implode(', ', array_filter([$values['birth_place'], $values['birth_date']]));
        foreach (['left', 'right'] as $side) {
            foreach (['title', 'name', 'rank'] as $part) $values['signer_'.$side.'_'.$part] = $data['template']['signer_'.$side.'_'.$part] ?? '';
        }
        $values['verification_qr'] = $data['verification_url'] ?? '';
        $values['verification_qr_svg'] = $values['verification_qr']
            ? (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(240)->margin(0)->errorCorrection('H')->generate($values['verification_qr'])
            : '';
        foreach ($data['scores'] ?? [] as $key => $score) {
            $values['score_'.$key] = is_numeric($score['value'])
                ? rtrim(rtrim(number_format((float) $score['value'], 2, '.', ''), '0'), '.') : (string) ($score['value'] ?? '');
            $values['words_'.$key] = $score['words'];
        }
        return $values;
    }

    public static function sample(string $type = 'dan'): array
    {
        $data = ['name' => 'NAMA PESERTA', 'number' => 'KKI/DAN/1/000001/2026', 'member_number' => 'SMT-000001', 'no_id_msh' => 'MSH-KKI-000123',
            'birth_place' => 'Medan', 'birth_date' => '2000-01-01', 'province' => 'SUMATERA UTARA', 'city' => 'MEDAN',
            'dojo' => 'DOJO CONTOH', 'rank' => 'DAN II', 'belt' => 'Hitam', 'exam_name' => 'Ujian Kenaikan Sabuk',
            'exam_date' => '2026-11-01', 'location' => 'Medan', 'issue_place' => 'Jakarta', 'issue_date' => '2026-11-01',
            'verification_url' => url('/verifikasi-sertifikat/contoh'),
            'decision_number' => '001/KKI/XI/2026', 'decision_date' => '2026-11-01', 'examiner' => 'Nama Penguji', 'result' => 'Lulus'];
        $data['template'] = ['signer_left_title' => 'Ketua Umum', 'signer_left_name' => 'Nama Ketua Umum', 'signer_left_rank' => 'DAN VIII',
            'signer_right_title' => 'Ketua Dewan Guru', 'signer_right_name' => 'Nama Ketua Dewan Guru', 'signer_right_rank' => 'DAN VIII'];
        if ($type === 'kyu') {
            $data['rank'] = 'KYU 2'; $data['belt'] = 'Coklat Strip'; $data['number'] = 'KKI/KYU/1/000001/2026';
        }
        foreach (ExamCertificates::MATERIALS as $key => $label) $data['scores'][$key] = ['value' => 75, 'words' => 'Tujuh Puluh Lima'];
        return self::values($data);
    }
}
