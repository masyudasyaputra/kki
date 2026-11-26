<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Official $official) {
            if ($official->exists && !$official->official_number_manual && $official->isDirty(['level', 'province_id', 'city_id', 'sk_expiry_date'])) {
                $official->unsetRelation('province')->unsetRelation('city');
                $official->official_number = app(\App\Services\OfficialNumbers::class)->automatic($official);
                if (static::where('official_number', $official->official_number)->whereKeyNot($official->id)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['official_number' => 'Nomor otomatis sudah digunakan. Sesuaikan melalui Manajemen Nomor ID sebelum mengubah wilayah atau tahun SK.']);
                }
            }
        });
        static::created(fn (Official $official) => app(\App\Services\OfficialNumbers::class)->issue($official));
    }

    protected $fillable = [
        'user_id',
        'photo_path',
        'name',
        'position',
        'phone_number',
        'sk_number',
        'sk_expiry_date',
        'sk_document_path',
        'level',
        'province_id',
        'city_id'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $path = $this->user_id ? $this->user?->photo : $this->photo_path;
        return $path ? url('storage/'.$path) : null;
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Accessor Status Aktif
    public function getIsActiveAttribute()
    {
        if (!$this->sk_expiry_date)
            return false;
        return \Carbon\Carbon::parse($this->sk_expiry_date)->isFuture();
    }
}
