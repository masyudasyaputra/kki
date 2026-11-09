<?php

namespace App\Http\Controllers;

use App\Models\ExamCertificate;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    public function show(Request $request, ExamCertificate $certificate)
    {
        abort_unless($request->hasValidSignature(), 404);

        return view('certificates.verify', [
            'certificate' => $certificate,
            'data' => $certificate->snapshot ?? [],
        ]);
    }
}
