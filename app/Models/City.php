<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public function getRegistrationCodeAttribute(): string
    {
        $code = trim((string) $this->code);
        return preg_match('/^\d{2}\.?\d{2}$/D', $code) ? str_replace('.', '', $code) : 'UNK';
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
