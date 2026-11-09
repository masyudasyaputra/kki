<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleManager
{
    /**
     * Usage:
     *   ->middleware('role:pb,pengprov,pengcab,admin_dojo,penguji,admin_pengprov,admin_pengcab')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Biarkan auth middleware yang handle redirect login
        if (!$user) {
            return redirect()->guest(route('login'));
        }

        // Jika middleware dipanggil tanpa parameter role -> anggap lolos
        if (empty($roles)) {
            return $next($request);
        }

        // Allowed roles dari parameter middleware
        $allowed = collect($roles)
            ->flatMap(fn($r) => explode(',', (string) $r))
            ->map(fn($r) => strtolower(trim((string) $r)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Owned roles: primary role + roles tambahan (json/array/string)
        $owned = $this->getOwnedRoles($user);

        // Lolos jika ada irisan
        if (count(array_intersect($allowed, $owned)) > 0) {
            return $next($request);
        }

        // Jangan redirect di middleware role (rawan loop).
        abort(403, 'AKSES DITOLAK.');
    }

    /**
     * Ambil semua role yang dimiliki user (primary + tambahan)
     */
    protected function getOwnedRoles($user): array
    {
        return $user->allRoles();
    }

    /**
     * Kalau suatu saat perlu dipakai untuk menentukan akses tertinggi:
     * pb > pengprov/admin_pengprov > pengcab/admin_pengcab > admin_dojo > penguji > member
     */
    public static function highestRole(array $owned): string
    {
        $priority = [
            'super_admin',
            'pb',
            'dewan_guru_pusat',
            'pengprov',
            'admin_pengprov',
            'pengcab',
            'admin_pengcab',
            'admin_dojo',
            'penguji',
            'panitia_ujian',
            'verifikator_anggota_pb',
            'verifikator_ujian_pb',
            'pengelola_sertifikat_pb',
            'verifikator_pengprov',
            'verifikator_pengcab',
            'member',
        ];

        foreach ($priority as $r) {
            if (in_array($r, $owned, true))
                return $r;
        }

        return $owned[0] ?? 'member';
    }
}
