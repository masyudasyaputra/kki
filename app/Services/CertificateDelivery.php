<?php
namespace App\Services;

use App\Models\{Exam, ExamCertificate, User, BeltLevel, BeltHistory};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CertificateDelivery
{
    public function send(User $actor, Exam $exam, int $participantId): bool
    {
        $service = app(ExamCertificates::class);
        $service->authorizeIssuance($actor, $exam);
        return DB::transaction(function () use ($service, $actor, $exam, $participantId) {
            $exam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            if ($exam->status !== 'completed') throw ValidationException::withMessages(['certificate' => 'Finalisasi ujian sebelum mengirim sertifikat.']);
            $person = $service->participants($actor, $exam)->whereKey($participantId)->lockForUpdate()->firstOrFail();
            $certificate = ExamCertificate::where('exam_participant_id', $person->id)->lockForUpdate()->first();
            if ($certificate?->sent_at) return false;
            if (!$certificate) {
                $template = $service->templates($actor)->where('exam_type', $exam->exam_type)->find($exam->certificate_settings['template_id'] ?? 0);
                if (!$template) throw ValidationException::withMessages(['certificate' => 'Pilih template sertifikat pada pengaturan sesi terlebih dahulu.']);
                $identity = $person->certificate_identity ?? [];
                $snapshot = $service->payload($exam, $person, $template, $identity, true);
                $keys = array_column($template->settings['pdf_layout'] ?? [], 'key');
                $needsPlace = ($template->settings['render_mode'] ?? 'builtin') !== 'pdf' || array_intersect($keys, ['birth_place', 'birth_details']);
                $needsDate = ($template->settings['render_mode'] ?? 'builtin') !== 'pdf' || array_intersect($keys, ['birth_date', 'birth_details']);
                if (($needsPlace && empty($identity['birth_place'])) || ($needsDate && empty($identity['birth_date']))) {
                    throw ValidationException::withMessages(['certificate' => 'Lengkapi tempat dan tanggal lahir melalui tombol Data sertifikat.']);
                }
                if (in_array('no_id_msh', $keys, true) && empty($identity['no_id_msh'])) {
                    throw ValidationException::withMessages(['certificate' => 'Masukkan Nomor ID MSH KKI peserta melalui tombol Data sertifikat.']);
                }
                $number = 'KKI/'.strtoupper($exam->exam_type).'/'.$exam->id.'/'.str_pad($person->id, 6, '0', STR_PAD_LEFT).'/'.$exam->execution_date->format('Y');
                $snapshot['number'] = $number;
                $snapshot['photo'] = $identity['photo'] ?? null;
                $certificate = ExamCertificate::create(['exam_participant_id' => $person->id, 'issued_by' => $actor->id, 'number' => $number, 'snapshot' => $snapshot]);
                $snapshot = \App\Services\CertificateVerificationLinks::attach($snapshot, $certificate->id);
                $certificate->update(['snapshot' => $snapshot]);
            }
            $belt = BeltLevel::where('kyu_dan', $certificate->snapshot['rank'])->where('name', $certificate->snapshot['belt'])->first();
            if (!$belt || (!empty($certificate->snapshot['belt_level_id']) && (int) $certificate->snapshot['belt_level_id'] !== (int) $belt->id)) {
                throw ValidationException::withMessages(['certificate' => 'Tingkat sabuk pada sertifikat tidak ditemukan. Periksa data tingkat sabuk.']);
            }
            $member = User::whereKey($person->user_id)->lockForUpdate()->firstOrFail();
            $currentBelt = $belt;
            if (!$belt->is_active) {
                $name = app(BeltCatalog::class)->legacyTarget($belt);
                $currentBelt = $name ? BeltLevel::active()->where('name', $name)->where('kyu_dan', 'like', '%Kyu%')->first() : null;
                if (!$currentBelt) throw ValidationException::withMessages(['certificate' => 'Padanan sabuk aktif untuk sertifikat lama belum tersedia.']);
            }
            // An older certificate may be delivered later; preserve any newer exam promotion.
            $hasNewerPromotion = DB::table('belt_histories')->join('exams', 'exams.id', '=', 'belt_histories.exam_id')
                ->where('belt_histories.user_id', $member->id)->where('exams.execution_date', '>', $exam->execution_date)->exists();
            if (!$hasNewerPromotion) $member->update(['belt_level_id' => $currentBelt->id]);
            BeltHistory::updateOrCreate(['user_id' => $member->id, 'exam_id' => $exam->id], [
                'belt_level_id' => $belt->id, 'achieved_at' => $exam->execution_date,
                'description' => 'Lulus ujian '.$exam->name.' · Sertifikat '.$certificate->number,
            ]);
            $person->result = 'passed'; $person->save();
            $certificate->sent_at = now(); $certificate->sent_by = $actor->id; $certificate->save();
            return true;
        });
    }
}
