<?php

namespace App\Services;

class CertificatePdfLayout
{
    public static function fields(): array
    {
        $fields = ['name' => 'Nama peserta', 'number' => 'Nomor sertifikat', 'member_number' => 'ID anggota',
            'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'birth_details' => 'Tempat / tanggal lahir',
            'province' => 'Provinsi', 'city' => 'Kabupaten / Kota', 'dojo' => 'Dojo / Ranting',
            'rank' => 'Tingkat DAN / KYU', 'belt' => 'Warna sabuk', 'exam_name' => 'Nama ujian',
            'exam_date' => 'Tanggal ujian', 'location' => 'Lokasi ujian', 'issue_place' => 'Tempat penerbitan',
            'issue_date' => 'Tanggal penerbitan', 'decision_number' => 'Nomor SK', 'decision_date' => 'Tanggal SK',
            'examiner' => 'Nama penguji', 'result' => 'Hasil ujian',
            'signer_left_name' => 'Nama penandatangan kiri', 'signer_right_name' => 'Nama penandatangan kanan', 'custom' => 'Teks bebas'];
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
        foreach (['left', 'right'] as $side) $values['signer_'.$side.'_name'] = $data['template']['signer_'.$side.'_name'] ?? '';
        foreach ($data['scores'] ?? [] as $key => $score) {
            $values['score_'.$key] = is_numeric($score['value'])
                ? rtrim(rtrim(number_format((float) $score['value'], 2, '.', ''), '0'), '.') : (string) ($score['value'] ?? '');
            $values['words_'.$key] = $score['words'];
        }
        return $values;
    }

    public static function sample(string $type = 'dan'): array
    {
        $data = ['name' => 'NAMA PESERTA', 'number' => 'KKI/DAN/1/000001/2026', 'member_number' => 'SMT-000001',
            'birth_place' => 'Medan', 'birth_date' => '2000-01-01', 'province' => 'SUMATERA UTARA', 'city' => 'MEDAN',
            'dojo' => 'DOJO CONTOH', 'rank' => 'DAN II', 'belt' => 'Hitam', 'exam_name' => 'Ujian Kenaikan Sabuk',
            'exam_date' => '2026-11-01', 'location' => 'Medan', 'issue_place' => 'Jakarta', 'issue_date' => '2026-11-01',
            'decision_number' => '001/KKI/XI/2026', 'decision_date' => '2026-11-01', 'examiner' => 'Nama Penguji', 'result' => 'Lulus'];
        if ($type === 'kyu') {
            $data['rank'] = 'KYU 2'; $data['belt'] = 'Cokelat'; $data['number'] = 'KKI/KYU/1/000001/2026';
        }
        foreach (ExamCertificates::MATERIALS as $key => $label) $data['scores'][$key] = ['value' => 75, 'words' => 'Tujuh Puluh Lima'];
        return self::values($data);
    }
}
