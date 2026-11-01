<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MembershipReview
{
    public const ROLES = [
        'verifikator_pb' => 'Tim Verifikasi PB',
        'verifikator_dewan_guru' => 'Tim Verifikasi Dewan Guru',
        'verifikator_pengprov' => 'Tim Verifikasi Pengprov',
        'verifikator_pengcab' => 'Tim Verifikasi Pengcab',
    ];

    public const FIELDS = ['name', 'parent_name', 'email', 'whatsapp', 'province_id', 'city_id', 'district', 'dojo_id', 'belt_level_id'];

    public function level(User $actor): string
    {
        foreach (array_keys(self::ROLES) as $role) {
            if ($actor->hasRole($role)) return str_replace('verifikator_', '', $role);
        }
        abort(403);
    }

    public function members(User $actor): Builder
    {
        $query = User::where('role', 'member');
        return match ($this->level($actor)) {
            'pb', 'dewan_guru' => $query,
            'pengprov' => $actor->province_id ? $query->where('province_id', $actor->province_id) : $query->whereRaw('1 = 0'),
            'pengcab' => $actor->province_id && $actor->city_id
                ? $query->where('province_id', $actor->province_id)->where('city_id', $actor->city_id)
                : $query->whereRaw('1 = 0'),
        };
    }

    public function data(User $member): array
    {
        return [
            'name' => ['Nama lengkap', $member->name],
            'parent_name' => ['Nama orang tua', $member->parent_name],
            'email' => ['Email', $member->email],
            'whatsapp' => ['WhatsApp', $member->whatsapp],
            'province_id' => ['Provinsi', $member->province?->name],
            'city_id' => ['Kabupaten / Kota', $member->city?->name],
            'district' => ['Kecamatan', $member->district],
            'dojo_id' => ['Dojo', $member->dojo?->name],
            'belt_level_id' => ['Tingkat sabuk', $member->beltLevel?->name],
        ];
    }

    public function fingerprint(User $member): string
    {
        return hash('sha256', json_encode([$member->only(self::FIELDS), $this->data($member)], JSON_UNESCAPED_UNICODE));
    }
}
