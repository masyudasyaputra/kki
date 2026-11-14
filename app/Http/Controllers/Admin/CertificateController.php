<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Exam, CertificateTemplate, ExamCertificate};
use App\Services\ExamCertificates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    public function index(Request $request, Exam $exam, ExamCertificates $service)
    {
        $service->authorize($request->user(), $exam);
        return redirect()->route('admin.exams.show', $exam);
    }

    public function settings(Request $request, Exam $exam, ExamCertificates $service)
    {
        $service->authorizeIssuance($request->user(), $exam);
        $data = $request->validate(['exam_type' => ['required', Rule::in(['dan', 'kyu'])], 'template_id' => ['required', 'integer'],
            'issue_place' => ['required', 'string', 'max:255'], 'issue_date' => ['required', 'date'],
            'decision_number' => ['nullable', 'string', 'max:255'], 'decision_date' => ['nullable', 'date'],
            'sk_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $service->templates($request->user())->where('exam_type', $data['exam_type'])->findOrFail($data['template_id']);
        abort_unless($exam->exam_type === $data['exam_type'], 422, 'Jenis sertifikat harus sesuai ujian yang diverifikasi.');
        $oldDocument = $exam->certificate_settings['decision_document_path'] ?? null;
        if ($request->hasFile('sk_document')) {
            $data['decision_document_path'] = $request->file('sk_document')->store('sk-documents/exams', 'local');
        } else {
            $data['decision_document_path'] = $oldDocument;
        }
        unset($data['sk_document']);
        $exam->update(['certificate_settings' => $data]);
        if (!empty($data['decision_document_path']) && $data['decision_document_path'] !== $oldDocument && $oldDocument) Storage::disk('local')->delete($oldDocument);
        return redirect()->route('admin.exams.show', $exam)->with('success', 'Pengaturan sertifikat sesi ujian disimpan.');
    }

    public function issue(Request $request, Exam $exam, int $participant, ExamCertificates $service)
    {
        $service->authorizeIssuance($request->user(), $exam);
        $identity = $request->validate(['birth_place' => ['required', 'string', 'max:150'], 'birth_date' => ['required', 'date', 'before_or_equal:today'], 'no_id_msh' => ['nullable', 'string', 'max:100'], 'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
        $certificate = DB::transaction(function () use ($request, $exam, $participant, $service, $identity) {
            $exam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            $person = $service->participants($request->user(), $exam)->whereKey($participant)->lockForUpdate()->firstOrFail();
            $template = $service->templates($request->user())->where('exam_type', $exam->exam_type)->findOrFail($exam->certificate_settings['template_id'] ?? 0);
            $snapshot = $service->payload($exam, $person, $template, $identity, true);
            $certificate = ExamCertificate::firstOrNew(['exam_participant_id' => $person->id]);
            $snapshot['photo'] = $request->hasFile('photo') ? $request->file('photo')->store('certificates/photos', 'public') : ($certificate->snapshot['photo'] ?? null);
            if ($certificate->exists) {
                $history = $certificate->previous_versions ?? [];
                $history[] = ['revision' => $certificate->revision, 'snapshot' => $certificate->snapshot, 'issued_by' => $certificate->issued_by, 'issued_at' => $certificate->updated_at->toIso8601String(), 'sent_at' => $certificate->sent_at?->toIso8601String(), 'sent_by' => $certificate->sent_by];
                $certificate->previous_versions = $history;
                $certificate->revision++;
                $certificate->sent_at = null;
                $certificate->sent_by = null;
            } else {
                $certificate->number = 'KKI/'.strtoupper($exam->exam_type).'/'.$exam->id.'/'.str_pad($person->id, 6, '0', STR_PAD_LEFT).'/'.$exam->execution_date->format('Y');
            }
            $snapshot['number'] = $certificate->number;
            $certificate->snapshot = $snapshot; $certificate->issued_by = $request->user()->id; $certificate->save();
            $snapshot['verification_url'] = URL::signedRoute('certificates.verify', ['certificate' => $certificate->id]);
            $certificate->snapshot = $snapshot; $certificate->save();
            return $certificate;
        });
        return redirect()->route('admin.exams.certificates.print', [$exam, $participant]);
    }

    public function preview(Request $request, Exam $exam, int $participant, ExamCertificates $service)
    {
        $service->authorizeIssuance($request->user(), $exam);
        $person = $service->participants($request->user(), $exam)->findOrFail($participant);
        $template = $service->templates($request->user())->where('exam_type', $exam->exam_type)->findOrFail($request->integer('template_id') ?: ($exam->certificate_settings['template_id'] ?? 0));
        $data = $service->payload($exam, $person, $template);
        $data['verification_url'] = url('/verifikasi-sertifikat/pratinjau');
        $draft = true;
        if (($data['template']['render_mode'] ?? '') === 'pdf') return $this->pdfView($data, $draft);
        return view('admin.certificates.print', compact('data', 'draft'));
    }

    public function print(Request $request, Exam $exam, int $participant, ExamCertificates $service)
    {
        $service->authorize($request->user(), $exam);
        $person = $service->participants($request->user(), $exam)->findOrFail($participant);
        $certificate = ExamCertificate::where('exam_participant_id', $person->id)->firstOrFail();
        $data = $certificate->snapshot;
        $data['verification_url'] = URL::signedRoute('certificates.verify', ['certificate' => $certificate->id]);
        $draft = false;
        if (($data['template']['render_mode'] ?? '') === 'pdf') return $this->pdfView($data, $draft);
        return view('admin.certificates.print', compact('data', 'draft'));
    }

    private function pdfView(array $data, bool $draft)
    {
        $pdfConfig = ['pdfUrl' => url('storage/'.$data['template']['pdf_path']), 'layout' => $data['template']['pdf_layout'] ?? [],
            'values' => \App\Services\CertificatePdfLayout::values($data), 'draft' => $draft,
            'filename' => ($draft ? 'DRAF-' : '').preg_replace('/[^a-zA-Z0-9_-]/', '-', $data['number'] ?? $data['name']).'.pdf'];
        return view('admin.certificates.pdf', compact('pdfConfig', 'data', 'draft'));
    }

    public function send(Request $request, Exam $exam, int $participant, \App\Services\CertificateDelivery $delivery)
    {
        $sent = $delivery->send($request->user(), $exam, $participant);
        return redirect()->route('admin.exams.show', $exam)->with('success', $sent ? 'Sertifikat berhasil dikirim ke akun anggota.' : 'Sertifikat sudah tersedia di akun anggota.');
    }

    public function sendAll(Request $request, Exam $exam, ExamCertificates $service, \App\Services\CertificateDelivery $delivery)
    {
        $service->authorizeIssuance($request->user(), $exam);
        if ($exam->status !== 'completed') throw \Illuminate\Validation\ValidationException::withMessages(['certificate' => 'Finalisasi ujian sebelum mengirim sertifikat.']);
        $sent = 0; $already = 0; $failed = [];
        foreach ($service->participants($request->user(), $exam)->get() as $person) {
            try {
                if ($delivery->send($request->user(), $exam, $person->id)) $sent++; else $already++;
            } catch (\Illuminate\Validation\ValidationException $error) {
                $failed[] = $person->user->name.': '.collect($error->errors())->flatten()->implode(' ');
            }
        }
        return redirect()->route('admin.exams.show', $exam)
            ->with('success', "$sent sertifikat dikirim; $already sudah dikirim; ".count($failed).' belum dapat dikirim.')
            ->with('certificate_delivery_errors', $failed);
    }

    public function identity(Request $request, Exam $exam, int $participant, ExamCertificates $service)
    {
        $service->authorizeIssuance($request->user(), $exam);
        $person = $service->participants($request->user(), $exam)->findOrFail($participant);
        $identity = $request->validate(['birth_place' => ['nullable', 'string', 'max:150'], 'birth_date' => ['nullable', 'date', 'before_or_equal:today'], 'no_id_msh' => ['nullable', 'string', 'max:100'], 'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
        $identity['photo'] = $request->hasFile('photo') ? $request->file('photo')->store('certificates/photos', 'public') : ($person->certificate_identity['photo'] ?? null);
        $person->certificate_identity = $identity; $person->save();
        return redirect()->route('admin.exams.show', $exam)->with('success', 'Data sertifikat peserta disimpan.');
    }

    public function member(Request $request, ExamCertificate $certificate)
    {
        abort_unless($certificate->sent_at && (int) $certificate->participant?->user_id === (int) $request->user()->id, 404);
        $data = $certificate->snapshot;
        $data['verification_url'] = URL::signedRoute('certificates.verify', ['certificate' => $certificate->id]);
        $draft = false;
        if (($data['template']['render_mode'] ?? '') === 'pdf') return $this->pdfView($data, $draft);
        return view('admin.certificates.print', compact('data', 'draft'));
    }
}
