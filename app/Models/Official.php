<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    protected static function booted(): void
    {
        static::created(fn (Official $official) => app(\App\Services\OfficialNumbers::class)->issue($official));
    }

    protected $fillable = [
        'user_id',
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
