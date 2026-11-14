<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;

class CertificateVerificationLinks
{
    public static function attach(array $data, int $certificateId): array
    {
        $data['verification_url'] = URL::signedRoute('certificates.verify', ['certificate' => $certificateId]);
        $data['signer_verification_urls'] = [];
        foreach (['left', 'center', 'right'] as $side) {
            if (empty($data['template']['signer_'.$side.'_name'])) continue;
            $parameters = ['certificate' => $certificateId, 'signer' => $side];
            if ($id = ($data['template']['signer_'.$side.'_user_id'] ?? null)) $parameters['signer_id'] = $id;
            $data['signer_verification_urls'][$side] = URL::signedRoute('certificates.verify', $parameters);
        }
        return $data;
    }
}
