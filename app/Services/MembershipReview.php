<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MembershipReview
{
    public const ROLES = [
        'verifikator_anggota_pb' => 'Verifikator Keanggotaan Pusat',
        'verifikator_pengprov' => 'Verifikator Keanggotaan Provinsi',
        'verifikator_pengcab' => 'Verifikator Keanggotaan Kabupaten / Kota',
    ];

    public const FIELDS = ['name', 'parent_name', 'email', 'whatsapp', 'province_id', 'city_id', 'district', 'address', 'dojo_id', 'belt_level_id', 'first_coach'];
    public const REQUIRES_VERIFICATION = ['belt_level_id', 'province_id', 'city_id', 'district', 'dojo_id'];

    public function level(User $actor): string
    {
        if ($actor->hasRole(['super_admin', 'pb'])) return 'pb';
        foreach (array_keys(self::ROLES) as $role) {
            if ($actor->hasRole($role)) return $role === 'verifikator_anggota_pb' ? 'pb' : str_replace('verifikator_', '', $role);
        }
        abort(403);
    }

    public function members(User $actor): Builder
    {
        $query = User::where('role', 'member');
        // A pending non-karateka account also uses the restricted member role.
        // Registration type determines eligibility for membership verification.
        if ($query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('users', 'registration_type')) {
            $query->where(fn ($q) => $q->whereNull('registration_type')->orWhere('registration_type', '!=', 'official'));
        }
        if ($this->level($actor) !== 'pb') {
            if ($query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('users', 'requested_belt_level_id')) {
                $query->where(function ($q) {
                    $q->where(fn ($r) => $r->whereNotNull('requested_belt_level_id')->whereDoesntHave('requestedBelt', fn ($b) => $b->whereRaw('LOWER(name) LIKE ?', ['%hitam%'])))
                        ->orWhere(fn ($r) => $r->whereNull('requested_belt_level_id')->whereDoesntHave('beltLevel', fn ($b) => $b->whereRaw('LOWER(name) LIKE ?', ['%hitam%'])));
                });
            } else {
                $query->whereDoesntHave('beltLevel', fn ($b) => $b->whereRaw('LOWER(name) LIKE ?', ['%hitam%']));
            }
        }
        return match ($this->level($actor)) {
            'pb' => $query,
            'pengprov' => $actor->province_id ? $query->where('province_id', $actor->province_id) : $query->whereRaw('1 = 0'),
            'pengcab' => $actor->province_id && $actor->city_id
                ? $query->where('province_id', $actor->province_id)->where('city_id', $actor->city_id)
                : $query->whereRaw('1 = 0'),
        };
    }

    public function data(User $member): array
    {
        $data = [
            'name' => ['Nama lengkap', $member->name],
            'parent_name' => ['Nama orang tua', $member->parent_name],
            'email' => ['Email', $member->email],
            'whatsapp' => ['WhatsApp', $member->whatsapp],
            'province_id' => ['Provinsi', $member->province?->name],
            'city_id' => ['Kabupaten / Kota', $member->city?->name],
            'district' => ['Kecamatan', $member->district],
            'address' => ['Alamat', $member->address],
            'dojo_id' => ['Dojo', $member->dojo?->name],
            'first_coach' => ['Pelatih awal', $member->first_coach],
            'belt_level_id' => [$member->requested_belt_level_id ? 'Tingkat sabuk yang diajukan' : 'Tingkat sabuk', $member->requested_belt_level_id ? $member->requestedBelt?->display_name : $member->beltLevel?->display_name],
        ];
        if ($member->requested_belt_level_id) $data['belt_diploma'] = ['Bukti ijazah sabuk', $member->belt_diploma_path ? 'Ijazah diunggah — wajib diperiksa' : 'Belum ada ijazah'];
        return $data;
    }

    public function fingerprint(User $member): string
    {
        return hash('sha256', json_encode([$member->only(array_merge(self::FIELDS, ['requested_belt_level_id', 'belt_diploma_path'])), $this->data($member)], JSON_UNESCAPED_UNICODE));
    }
}
