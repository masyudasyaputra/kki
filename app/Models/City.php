<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public function getRegistrationCodeAttribute(): string
    {
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $this->code));
        return $code ?: ($this->id ? str_pad((string) $this->id, 4, '0', STR_PAD_LEFT) : 'UNK');
    }

    protected $fillable = ['province_id', 'code', 'name'];

    public function districts()
    {
        return $this->hasMany(District::class)->orderBy('name');
    }

    // Ini yang kurang! Tambahkan ini agar tidak error
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function dojos()
    {
        return $this->hasMany(Dojo::class);
    }
}
