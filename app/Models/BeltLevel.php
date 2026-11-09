<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeltLevel extends Model
{
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);
        $rank = trim((string) $this->kyu_dan);

        return $rank !== '' ? $name.' ('.$rank.')' : $name;
    }

   protected $fillable = [
    'name', 
    'kyu_dan', 
    'order', 
    'is_active',
    'membership_fee' // Pastikan ini ada jika Anda memakainya di seeder
];

protected $casts = ['is_active' => 'boolean'];

public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function users()
{
    return $this->hasMany(User::class);
}
}
