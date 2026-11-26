<?php

namespace App\Services;

use App\Models\User;

class RoleAccess
{
    public const POSITIONS = [
        'super_admin' => 'Super Admin', 'pb' => 'Pengurus Pusat', 'dewan_guru_pusat' => 'Dewan Guru Pusat',
        'pengprov' => 'Pengurus Provinsi', 'pengcab' => 'Pengurus Kabupaten / Kota',
        'admin_dojo' => 'Admin Dojo', 'member' => 'Anggota',
    ];

    public const PERMISSIONS = [
        'verifikator_pengurus_pusat' => 'Admin Verifikasi Pengurus Pusat',
        'verifikator_pengurus_provinsi' => 'Admin Verifikasi Pengurus Provinsi',
        'verifikator_pengurus_pengcab' => 'Admin Verifikasi Pengurus Kabupaten / Kota',
        'verifikator_anggota_pb' => 'Verifikator Keanggotaan Pusat',
        'verifikator_pengprov' => 'Verifikator Keanggotaan Provinsi',
        'verifikator_pengcab' => 'Verifikator Keanggotaan Kabupaten / Kota',
        'panitia_ujian' => 'Panitia Ujian', 'penguji' => 'Penguji',
        'verifikator_ujian_pb' => 'Verifikator Ujian Pengurus Pusat',
        'pengelola_sertifikat_pb' => 'Pengelola Sertifikat Pengurus Pusat',
    ];

    public const NATIONAL = ['super_admin', 'pb', 'dewan_guru_pusat', 'verifikator_anggota_pb', 'verifikator_ujian_pb', 'pengelola_sertifikat_pb', 'verifikator_pengurus_pusat'];

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
