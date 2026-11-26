<?php

namespace App\Services;

use App\Models\{Exam, ExamParticipant, ExamCertificate};
use Illuminate\Validation\ValidationException;

class CertificateNumbers
{
    public function resolve(Exam $exam, ExamParticipant $person, ?string $custom, ?ExamCertificate $existing = null): string
    {
        $number = trim($custom ?? '') ?: ($existing?->number ?? 'KKI/'.strtoupper($exam->exam_type).'/'.$exam->id.'/'.str_pad($person->id, 6, '0', STR_PAD_LEFT).'/'.$exam->execution_date->format('Y'));
        $query = ExamCertificate::where('number', $number);
        if ($existing?->exists) $query->whereKeyNot($existing->id);
        if ($query->exists()) throw ValidationException::withMessages(['certificate_number' => 'Nomor registrasi sertifikat sudah digunakan.']);
        return $number;
    }
}
