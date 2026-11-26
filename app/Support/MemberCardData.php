<?php

namespace App\Support;

use App\Models\User;

class MemberCardData
{
    public static function verificationUrl(User $user): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('members.verify', ['member' => $user->id]);
    }

    public static function forUser(User $user): array
    {
        return [
            'accountId' => (string) $user->id,
            'name' => $user->name,
            'number' => $user->member_number ?: 'Belum diterbitkan',
            'dojo' => $user->dojo?->name ?? 'Belum dipilih',
            'rank' => $user->beltLevel?->display_name ?? 'Belum ditentukan',
            'province' => $user->province?->name ?? 'Belum diisi',
            'city' => $user->city?->name ?? 'Belum diisi',
            'status' => $user->expired_at?->isPast() ? 'Masa berlaku habis' : ($user->is_active ? 'Aktif' : 'Tidak aktif'),
            'verification' => match ($user->verification_status) {
                'approved' => 'Terverifikasi',
                'revision' => 'Perlu perbaikan',
                'rejected' => 'Ditolak',
                'pending' => 'Menunggu verifikasi',
                default => 'Belum diverifikasi',
            },
            'expiry' => $user->expired_at?->format('d/m/Y') ?? 'Belum tercatat',
            'theme' => MemberCardTheme::forBelt($user->beltLevel?->name),
            'logo' => asset('images/logo-kki-dark.png'),
            'photo' => $user->photo ? url('storage/'.$user->photo) : null,
        ];
    }
}
