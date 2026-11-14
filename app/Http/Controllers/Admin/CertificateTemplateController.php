<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Exam, CertificateTemplate};
use App\Services\ExamCertificates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    public function index(Request $request, ExamCertificates $service)
    {
        $service->authorizeTemplates($request->user());
        $templates = $service->templates($request->user())->orderBy('name')->get();
        $templateUsage = Exam::whereIn('certificate_settings->template_id', $templates->pluck('id')->flatMap(fn ($id) => [$id, (string) $id])->all())->get(['certificate_settings'])
            ->countBy(fn ($session) => (string) ($session->certificate_settings['template_id'] ?? ''));
        return view('admin.certificates.templates', compact('templates', 'templateUsage'));
    }

    public function template(Request $request, ExamCertificates $service)
    {
        $service->authorizeTemplates($request->user());
        $request->merge(['render_mode' => $request->input('render_mode', 'builtin')]);
        $data = $request->validate(['template_id' => ['nullable', 'integer'], 'name' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', Rule::in(['dan', 'kyu'])],
            'render_mode' => ['required', Rule::in(['builtin', 'pdf'])],
            'template_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'heading' => ['required_unless:render_mode,pdf', 'nullable', 'string', 'max:255'], 'organization' => ['required_unless:render_mode,pdf', 'nullable', 'string', 'max:255'],
            'title' => ['required_unless:render_mode,pdf', 'nullable', 'string', 'max:100'], 'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            'back_background' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            'signature_left' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'signature_center' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'signature_right' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        $template = !empty($data['template_id']) ? $service->templates($request->user())->findOrFail($data['template_id']) : new CertificateTemplate;
        if ($data['render_mode'] === 'pdf' && !$request->hasFile('template_pdf') && empty($template->settings['pdf_path'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['template_pdf' => 'Unggah desain PDF untuk template ini.']);
        }
        $settings = array_merge(ExamCertificates::defaults($data['exam_type']), $template->settings ?? [], collect($data)->except(['template_id', 'name', 'exam_type', 'template_pdf', 'background', 'back_background', 'signature_left', 'signature_center', 'signature_right'])->all());
        $settings['heading'] = 'PENGURUS PUSAT';
        if ($request->hasFile('template_pdf')) {
            $settings['pdf_path'] = $request->file('template_pdf')->store('certificates/pdf-templates', 'public');
            $settings['pdf_layout'] = [];
            $settings['pdf_pages'] = [];
        }
        foreach (['background', 'back_background', 'signature_left', 'signature_center', 'signature_right'] as $field) {
            if ($request->hasFile($field)) $settings[$field] = $request->file($field)->store('certificates/templates', 'public');
        }
        $template->fill(['name' => $data['name'], 'exam_type' => $data['exam_type'], 'settings' => $settings]);
        $template->province_id = null;
        $template->city_id = null;
        $template->save();
        if ($settings['render_mode'] === 'pdf') return redirect()->route('admin.certificate-templates.layout', $template);
        return redirect()->route('admin.certificate-templates.index')->with('success', 'Template sertifikat disimpan.');
    }

    public function destroyTemplate(Request $request, CertificateTemplate $template, ExamCertificates $service)
    {
        $this->editableTemplate($request, $template, $service);
        DB::transaction(function () use ($template) {
            $template = CertificateTemplate::whereKey($template->id)->lockForUpdate()->firstOrFail();
            if (Exam::whereIn('certificate_settings->template_id', [$template->id, (string) $template->id])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['template' => 'Template masih digunakan oleh sesi ujian. Pilih template pengganti pada pengaturan sesi tersebut sebelum menghapusnya.']);
            }
            // Issued certificates retain snapshots referencing these immutable uploaded files.
            $template->delete();
        });
        return redirect()->route('admin.certificate-templates.index')->with('success', 'Template berhasil dihapus. Sertifikat yang sudah diterbitkan tetap tersimpan.');
    }

    private function editableTemplate(Request $request, CertificateTemplate $template, ExamCertificates $service): void
    {
        $service->authorizeTemplates($request->user());
        $service->templates($request->user())->findOrFail($template->id);
    }

    public function layout(Request $request, CertificateTemplate $template, ExamCertificates $service)
    {
        $this->editableTemplate($request, $template, $service);
        abort_unless(!empty($template->settings['pdf_path']), 404);
        $editorConfig = ['pdfUrl' => url('storage/'.$template->settings['pdf_path']), 'fields' => \App\Services\CertificatePdfLayout::fields(),
            'values' => \App\Services\CertificatePdfLayout::sample($template->exam_type), 'layout' => $template->settings['pdf_layout'] ?? [],
            'saveUrl' => route('admin.certificate-templates.layout.save', $template), 'csrf' => csrf_token(), 'version' => hash('sha256', json_encode($template->settings))];
        return view('admin.certificates.layout', compact('template', 'editorConfig'));
    }

    public function saveLayout(Request $request, CertificateTemplate $template, ExamCertificates $service)
    {
        $this->editableTemplate($request, $template, $service);
        $data = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'pages' => ['required', 'array', 'min:1', 'max:10'],
            'pages.*' => ['required', 'array:width,height'],
            'pages.*.width' => ['required', 'numeric', 'min:20', 'max:1200'], 'pages.*.height' => ['required', 'numeric', 'min:20', 'max:1200'],
            'layout' => ['required', 'array', 'min:1', 'max:150'],
            'layout.*' => ['required', 'array:key,page,x,y,width,height,font_size,line_height,wrap,align,font,bold,color,text'],
            'layout.*.key' => ['required', Rule::in(array_keys(\App\Services\CertificatePdfLayout::fields()))],
            'layout.*.page' => ['required', 'integer', 'min:1', 'max:10'],
            'layout.*.x' => ['required', 'numeric', 'min:0', 'max:1200'], 'layout.*.y' => ['required', 'numeric', 'min:0', 'max:1200'],
            'layout.*.width' => ['required', 'numeric', 'min:1', 'max:1200'], 'layout.*.height' => ['sometimes', 'numeric', 'min:1', 'max:1200'],
            'layout.*.font_size' => ['required', 'numeric', 'min:4', 'max:72'],
            'layout.*.line_height' => ['sometimes', 'numeric', 'min:4', 'max:144'], 'layout.*.wrap' => ['sometimes', 'boolean'],
            'layout.*.align' => ['required', Rule::in(['left', 'center', 'right'])], 'layout.*.font' => ['required', Rule::in(['serif', 'sans', 'mono'])],
            'layout.*.bold' => ['required', 'boolean'], 'layout.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'layout.*.text' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach ($data['layout'] as $row) {
            $page = $data['pages'][$row['page'] - 1] ?? null;
            $height = in_array($row['key'], ['verification_qr', 'signer_left_qr', 'signer_center_qr', 'signer_right_qr'], true)
                ? (float) ($row['height'] ?? 0)
                : (float) $row['font_size'] * 25.4 / 72;
            if (!$page || $height <= 0 || $row['x'] + $row['width'] > $page['width'] + .1 || $row['y'] + $height > $page['height'] + .1) {
                throw \Illuminate\Validation\ValidationException::withMessages(['layout' => 'Posisi teks harus berada di dalam halaman PDF.']);
            }
        }
        return DB::transaction(function () use ($template, $data) {
            $template = CertificateTemplate::whereKey($template->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals(hash('sha256', json_encode($template->settings)), $data['version']), 409, 'Template berubah. Muat ulang sebelum menyimpan.');
            abort_unless(!empty($template->settings['pdf_path']), 422);
            $settings = $template->settings;
            $settings['pdf_layout'] = $data['layout']; $settings['pdf_pages'] = $data['pages']; $settings['render_mode'] = 'pdf';
            $template->update(['settings' => $settings]);
            return response()->json(['message' => 'Koordinat berhasil disimpan.', 'version' => hash('sha256', json_encode($settings))]);
        });
    }

}
