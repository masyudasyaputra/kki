<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\User;
use App\Services\ExamCertificates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CertificateSignerController extends Controller
{
    public function index(Request $request, ExamCertificates $service)
    {
        $service->authorizeTemplates($request->user());
        $templates = $service->templates($request->user())->orderBy('exam_type')->orderBy('name')->get();
        $signers = $this->eligibleSigners()->get();

        return view('admin.certificates.signers', compact('templates', 'signers'));
    }

    public function update(Request $request, CertificateTemplate $template, ExamCertificates $service)
    {
        $service->authorizeTemplates($request->user());
        $service->templates($request->user())->findOrFail($template->id);
        $eligibleIds = $this->eligibleSigners()->pluck('users.id')->all();
        $data = $request->validate([
            'signer_left_user_id' => ['required', 'integer', Rule::in($eligibleIds), 'different:signer_right_user_id', 'different:signer_center_user_id'],
            'signer_left_title' => ['required', 'string', 'max:150'],
            'signer_center_user_id' => ['required', 'integer', Rule::in($eligibleIds), 'different:signer_right_user_id'],
            'signer_center_title' => ['required', 'string', 'max:150'],
            'signer_right_user_id' => ['required', 'integer', Rule::in($eligibleIds)],
            'signer_right_title' => ['required', 'string', 'max:150'],
        ], [
            'signer_left_user_id.in' => 'Pilih user aktif yang memiliki tingkat sabuk DAN.',
            'signer_right_user_id.in' => 'Pilih user aktif yang memiliki tingkat sabuk DAN.',
            'signer_center_user_id.in' => 'Pilih user aktif yang memiliki tingkat sabuk DAN.',
            'signer_center_user_id.required' => 'Pilih penandatangan tengah.',
            'signer_center_title.required' => 'Isi jabatan penandatangan tengah.',
            'signer_left_user_id.different' => 'Setiap posisi penandatangan harus menggunakan user yang berbeda.',
            'signer_center_user_id.different' => 'Setiap posisi penandatangan harus menggunakan user yang berbeda.',
        ]);

        DB::transaction(function () use ($template, $data) {
            $template = CertificateTemplate::whereKey($template->id)->lockForUpdate()->firstOrFail();
            $settings = $template->settings ?? [];
            foreach (['left', 'center', 'right'] as $side) {
                $user = User::with('beltLevel')->findOrFail($data['signer_'.$side.'_user_id']);
                $settings['signer_'.$side.'_user_id'] = $user->id;
                $settings['signer_'.$side.'_title'] = trim($data['signer_'.$side.'_title']);
                $settings['signer_'.$side.'_name'] = $user->name;
                $settings['signer_'.$side.'_rank'] = $user->beltLevel?->kyu_dan ?? '';
            }
            $template->update(['settings' => $settings]);
        });

        return back()->with('success', 'Penandatangan sertifikat berhasil ditetapkan dari data user.');
    }

    private function eligibleSigners()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('beltLevel', fn ($query) => $query->whereRaw('LOWER(kyu_dan) LIKE ?', ['%dan%']))
            ->with('beltLevel')
            ->orderBy('users.name');
    }
}
