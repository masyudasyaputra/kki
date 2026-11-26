<?php

namespace App\Services;

use App\Models\User;

class RoleAccess
{
    public const SERVICE_POSITIONS = [
        'koordinator_nasional_sim_kki' => 'Koordinator Nasional SIM-KKI',
        'admin_utama_bantuan_teknis' => 'Admin Utama & Bantuan Teknis Sistem',
        'verifikasi_data_sabuk_hitam' => 'Verifikasi Data Sabuk Hitam (DAN)',
        'bantuan_admin_surat_menyurat' => 'Bantuan Admin & Surat Menyurat',
        'adaptasi_database_digitalisasi' => 'Penyesuaian/Adaptasi Penggunaan Database & Digitalisasi',
    ];

    public const SERVICE_PERMISSIONS = [
        'koordinator_nasional_sim_kki' => ['dewan_guru_pusat'],
        'admin_utama_bantuan_teknis' => ['super_admin'],
        'verifikasi_data_sabuk_hitam' => ['verifikator_sabuk_hitam'],
        'bantuan_admin_surat_menyurat' => ['pengelola_sertifikat_pb'],
        'adaptasi_database_digitalisasi' => [],
    ];

    public static function effectiveRoles(array $roles): array
    {
        $effective = $roles;
        foreach ($roles as $role) $effective = array_merge($effective, self::SERVICE_PERMISSIONS[$role] ?? []);
        return array_values(array_unique(array_filter($effective)));
    }

    public static function serviceDesk(User $user): bool
    {
        return $user->hasRole(array_merge(['pb', 'super_admin'], array_keys(self::SERVICE_POSITIONS)));
    }

    public static function managesRegionData(User $user): bool
    {
        return self::managesUsers($user) || $user->hasRole('adaptasi_database_digitalisasi');
    }

    public static function archives(User $user): bool
    {
        return self::managesUsers($user) || $user->hasRole('bantuan_admin_surat_menyurat');
    }

    public const POSITIONS = [
        'super_admin' => 'Super Admin', 'pb' => 'Pengurus Pusat', 'dewan_guru_pusat' => 'Dewan Guru Pusat',
        'pengprov' => 'Pengurus Provinsi', 'pengcab' => 'Pengurus Kabupaten / Kota',
        'admin_dojo' => 'Admin Dojo',
        ...self::SERVICE_POSITIONS,
        'member' => 'Anggota',
    ];

    public const PERMISSIONS = [
        'verifikator_pengurus_pusat' => 'Admin Verifikasi Pengurus Pusat',
        'verifikator_pengurus_provinsi' => 'Admin Verifikasi Pengurus Provinsi',
        'verifikator_pengurus_pengcab' => 'Admin Verifikasi Pengurus Kabupaten / Kota',
        'verifikator_anggota_pb' => 'Verifikator Keanggotaan Pusat',
        'verifikator_sabuk_hitam' => 'Verifikator Data Sabuk Hitam (DAN)',
        'verifikator_pengprov' => 'Verifikator Keanggotaan Provinsi',
        'verifikator_pengcab' => 'Verifikator Keanggotaan Kabupaten / Kota',
        'panitia_ujian' => 'Panitia Ujian', 'penguji' => 'Penguji',
        'verifikator_ujian_pb' => 'Verifikator Ujian Pengurus Pusat',
        'pengelola_sertifikat_pb' => 'Pengelola Sertifikat Pengurus Pusat',
    ];

    public const NATIONAL = ['super_admin', 'pb', 'dewan_guru_pusat', 'verifikator_anggota_pb', 'verifikator_ujian_pb', 'pengelola_sertifikat_pb', 'verifikator_pengurus_pusat',
        'koordinator_nasional_sim_kki', 'admin_utama_bantuan_teknis', 'verifikasi_data_sabuk_hitam', 'bantuan_admin_surat_menyurat', 'adaptasi_database_digitalisasi', 'verifikator_sabuk_hitam'];

    public const OFFICIAL_REVIEWERS = ['verifikator_pengurus_pusat', 'verifikator_pengurus_provinsi', 'verifikator_pengurus_pengcab'];

    public static function reviewsOfficials(User $user): bool
    {
        return $user->hasRole(array_merge(['super_admin', 'pb', 'pengprov', 'admin_pengprov'], self::OFFICIAL_REVIEWERS));
    }

    public static function nationalOfficials(User $user): bool
    {
        return self::managesUsers($user) || $user->hasRole('verifikator_pengurus_pusat');
    }

    public static function officialLevels(User $user): array
    {
        if (self::nationalOfficials($user)) return ['pusat', 'provinsi', 'pengcab'];
        return $user->hasRole('verifikator_pengurus_provinsi') ? ['provinsi', 'pengcab'] : ['pengcab'];
    }

    public static function managesUsers(User $user): bool
    {
        return $user->hasRole(['pb', 'super_admin']);
    }

    public static function certificates(User $user): bool
    {
        return $user->hasRole(['super_admin', 'pb', 'pengelola_sertifikat_pb']);
    }
    public static function certificateTemplates(User $user): bool
    {
        return self::certificates($user) || $user->hasRole(ExamAuthority::REGIONAL);
    }

    public static function nationalExamReader(User $user): bool
    {
        return $user->hasRole(['super_admin', 'pb', 'dewan_guru_pusat', 'pengelola_sertifikat_pb']);
    }

    public static function primary(array $positions): string
    {
        foreach (array_keys(self::POSITIONS) as $position) if (in_array($position, $positions, true)) return $position;
        return 'member';
    }

    public static function migrateRoles(array $roles): array
    {
        $result = [];
        foreach ($roles as $role) {
            array_push($result, ...match ($role) {
                'verifikator_pb' => ['verifikator_anggota_pb'],
                'verifikator_dewan_guru' => ['dewan_guru_pusat', 'verifikator_anggota_pb'],
                'admin_pengprov' => ['pengprov'], 'admin_pengcab' => ['pengcab'],
                default => [$role],
            });
        }
        return array_values(array_unique(array_filter($result)));
    }
}
