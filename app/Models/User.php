<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            $number = $user->getAttribute('member_number_override');
            if ($number && preg_match('/(^|-)MSH(-|$)/i', $number) && !$user->isBlackBelt()) {
                if ($user->isDirty('belt_level_id') && !$user->isDirty('member_number_override')) {
                    $corrected = preg_replace('/(^|-)MSH-?/i', '$1', $number);
                    if (strlen($corrected) < 3 || static::where('member_number_override', $corrected)->whereKeyNot($user->id)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['belt_level_id' => 'Nomor tanpa kode MSH sudah dipakai atau tidak valid. Atur Nomor ID anggota sebelum mengoreksi sabuk.']);
                    }
                    $user->setAttribute('member_number_override', $corrected);
                } else {
                    throw \Illuminate\Validation\ValidationException::withMessages(['member_number_override' => 'Kode MSH hanya boleh digunakan anggota yang sudah sabuk hitam.']);
                }
            }
            if (array_key_exists('verification_status', $user->getAttributes()) && $user->isDirty(\App\Services\MembershipReview::REQUIRES_VERIFICATION)) {
                $user->verification_status = 'pending';
            }
        });
    }

    public function membershipVerifications()
    {
        return $this->hasMany(MembershipVerification::class, 'member_id');
    }

    protected $fillable = [
        'registration_type',
        'requested_position',
        'name',
        'email',
        'google_id',
        'password',

        // Role utama (primary)
        'role',

        // Multi-role tambahan (JSON)
        'roles',
        'positions',

        'is_active',
        'province_id',
        'city_id',
        'district',
        'address',
        'first_coach',
        'dojo_id',
        'belt_level_id',
        'whatsapp',
        'parent_name',
        'expired_at',
    ];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'public_directory_consent' => 'boolean',
            'expired_at' => 'datetime',
            'roles' => 'array',
            'positions' => 'array',
        ];
    }

    /**
     * Mengecek apakah user punya role tertentu.
     * - Support: $user->hasRole('penguji')
     * - Support: $user->hasRole(['pb','pengprov'])
     * - Support multi-role via kolom JSON roles + kolom role (primary)
     */
    public function hasRole(string|array $roles): bool
    {
        $userRoles = $this->allRoles();

        if (is_array($roles)) {
            foreach ($roles as $r) {
                if (in_array($r, $userRoles, true))
                    return true;
            }
            return false;
        }

        return in_array($roles, $userRoles, true);
    }

    /**
     * Ambil semua role user (gabungan role utama + roles tambahan),
     * tanpa duplikasi dan tanpa nilai kosong.
     */
    public function allRoles(): array
    {
        $roles = is_array($this->roles) ? $this->roles : [];
        $roles = array_merge($roles, is_array($this->positions) ? $this->positions : []);
        $roles[] = $this->role;

        return array_values(array_unique(array_filter($roles)));
    }

    // --- RELASI ---
    public function scopeMembers(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $schema = $this->getConnection()->getSchemaBuilder();
        $columns = array_filter(['positions', 'roles'], fn ($column) => $schema->hasColumn($this->getTable(), $column));

        return $query->where(function ($members) use ($columns) {
            $members->where('role', 'member');
            foreach ($columns as $column) $members->orWhereJsonContains($column, 'member');
        });
    }


    public function province()
    {
        return $this->belongsTo(Province::class);
    }
    public function official()
    {
        return $this->hasOne(Official::class);
    }
    public function qualifications()
    {
        return $this->hasMany(OfficialQualification::class);
    }
    public function requestedBelt()
    {
        return $this->belongsTo(BeltLevel::class, 'requested_belt_level_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function dojo()
    {
        return $this->belongsTo(Dojo::class);
    }

    public function beltLevel()
    {
        return $this->belongsTo(BeltLevel::class);
    }

    /**
     * Jika Anda memiliki tabel payments, pastikan relasi ini ada.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function assignedExamsAsExaminer()
    {
        return $this->belongsToMany(Exam::class, 'exam_examiners', 'user_id', 'exam_id')
            ->withTimestamps();
    }

    // --- ATTRIBUTES ---

    public function getMemberNumberAttribute(): string
    {
        return $this->getAttribute('member_number_override') ?: $this->automatic_member_number;
    }

    public function getAutomaticMemberNumberAttribute(): string
    {
        $belt = $this->relationLoaded('beltLevel') || $this->belt_level_id ? $this->beltLevel : null;
        $prefix = str_contains(mb_strtolower($belt?->name ?? ''), 'hitam') ? 'MSH-' : '';

        return $prefix . ($this->province?->member_prefix ?? 'UNK') . '-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isBlackBelt(): bool
    {
        $belt = $this->isDirty('belt_level_id') ? BeltLevel::find($this->belt_level_id) : $this->beltLevel;
        return str_contains(mb_strtolower($belt?->name ?? ''), 'hitam');
    }

    public function scopeWhereMemberNumber($query, string $number)
    {
        // Compatibility with older schemas during rollout and isolated test databases.
        if (!$this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'member_number_override')) {
            return $this->scopeWhereAutomaticMemberNumber($query, $number);
        }

        return $query->where(function ($numbers) use ($number) {
            $numbers->where('member_number_override', strtoupper(trim($number)))
                ->orWhere(function ($automatic) use ($number) {
                    $automatic->whereNull('member_number_override')->whereAutomaticMemberNumber($number);
                });
        });
    }

    public function scopeWhereAutomaticMemberNumber($query, string $number)
    {
        if (!preg_match('/^(MSH-)?([A-Z]{3})-(\d{5,})$/', strtoupper(trim($number)), $matches)) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('id', (int) $matches[3]);
        if ($matches[1] !== '') {
            $query->whereHas('beltLevel', fn ($belt) => $belt->whereRaw('LOWER(name) LIKE ?', ['%hitam%']));
        }
        if ($matches[2] === 'UNK') {
            return $query->whereDoesntHave('province');
        }

        $provinceIds = Province::all()->filter(fn ($province) => $province->member_prefix === $matches[2])->modelKeys();

        return $query->whereIn('province_id', $provinceIds);
    }

    /**
     * Status aktif berdasarkan pembayaran SUCCESS untuk belt yang sedang disandang.
     * Catatan: Pastikan status di DB memang 'SUCCESS' (bukan 'paid' / 'PAID' dll).
     */
    public function getIsActiveStatusAttribute(): bool
    {
        return $this->payments()
            ->where('belt_level_id', $this->belt_level_id)
            ->where('status', 'paid') // ✅ samakan dengan enum kamu
            ->exists();
    }

    // Relasi ke histori sabuk
    public function beltHistories()
    {
        return $this->hasMany(BeltHistory::class);
    }

    // Helper untuk mengambil sabuk terbaru
    public function currentBelt()
    {
        return $this->belongsTo(BeltLevel::class, 'current_belt_level_id');
    }
}
