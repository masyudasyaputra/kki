<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dojo extends Model
{
    public function getRegistrationNumberAttribute(): ?string
    {
        if ($this->getKey() === null) {
            return null;
        }

        $province = $this->city?->province ?? $this->province;

        return 'KKI-' . ($province?->member_prefix ?? 'UNK') . '-'.($this->city?->registration_code ?? 'UNK').'-'
            . str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'name',
        'province_id',
        'city_id',
        'address',
        'sensei_name',
        'phone_number',
        'sk_number',
        'sk_expiry_date',
        'sk_document_path',
    ];
    // Logika otomatis: Aktif jika SK belum expired
    public function getIsActiveAttribute()
    {
        if (!$this->sk_expiry_date)
            return false;
        return \Carbon\Carbon::parse($this->sk_expiry_date)->isFuture();
    }
    // Relasi ke Kota
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function members()
    {
        // Dojo memiliki banyak User melalui kolom dojo_id
        return $this->hasMany(User::class, 'dojo_id');
    }
}
