<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class UploadedCertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $path = 'certificates/pdf-templates/sertifikat-kki-343x223.pdf';
        if (!Storage::disk('public')->exists($path)) {
            throw new \RuntimeException('File template PDF belum tersedia pada storage publik: '.$path);
        }

        foreach (['dan' => 'DAN', 'kyu' => 'Kyu'] as $type => $label) {
            CertificateTemplate::firstOrCreate(
                ['name' => 'Sertifikat KKI 343x223 - '.$label, 'exam_type' => $type],
                ['province_id' => null, 'city_id' => null, 'settings' => [
                    'render_mode' => 'pdf',
                    'pdf_path' => $path,
                    'pdf_layout' => [],
                    'pdf_pages' => [
                        ['width' => 342.9, 'height' => 222.96],
                        ['width' => 342.9, 'height' => 222.96],
                    ],
                    'heading' => 'PENGURUS PUSAT',
                    'organization' => 'KUSHIN RYU M KARATE-DO INDONESIA',
                    'title' => 'IJAZAH',
                    'color' => $type === 'dan' ? '#9b773b' : '#ad342c',
                    'signer_left_title' => 'Ketua Umum',
                    'signer_left_name' => '',
                    'signer_left_rank' => '',
                    'signer_right_title' => 'Ketua Dewan Guru',
                    'signer_right_name' => '',
                    'signer_right_rank' => '',
                ]]
            );
        }
    }
}
