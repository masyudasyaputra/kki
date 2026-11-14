<?php

namespace App\Http\Controllers;

use App\Models\ExamCertificate;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    public function show(Request $request, ExamCertificate $certificate)
    {
        abort_unless($request->hasValidSignature(), 404);
        $data = $certificate->snapshot ?? [];
        $selectedSide = $request->query('signer');
        if ($selectedSide !== null) {
            abort_unless(is_string($selectedSide) && in_array($selectedSide, ['left', 'center', 'right'], true), 404);
            abort_if(empty($data['template']['signer_'.$selectedSide.'_name']), 404);
            $signerId = $data['template']['signer_'.$selectedSide.'_user_id'] ?? null;
            abort_if($signerId && (string) $request->query('signer_id') !== (string) $signerId, 404);
        }

        return view('certificates.verify', [
            'certificate' => $certificate,
            'data' => $data, 'selectedSide' => $selectedSide,
        ]);
    }
}
