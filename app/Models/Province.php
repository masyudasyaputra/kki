<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public function getMemberPrefixAttribute(): string
    {
        $name = strtoupper(trim($this->name ?? ''));
        $name = preg_replace('/\s*\([^)]*\)$/u', '', $name);
        $name = preg_replace('/^(PROVINSI|PROV\.)\s+/', '', $name);

        return [
            'ACEH' => 'ACH', 'SUMATERA UTARA' => 'SMT', 'SUMUT' => 'SMT',
            'SUMATERA BARAT' => 'SMB', 'SUMBAR' => 'SMB',
            'SUMATERA SELATAN' => 'SMS', 'SUMSEL' => 'SMS',
            'RIAU' => 'RIA', 'KEPULAUAN RIAU' => 'KPR', 'JAMBI' => 'JMB',
            'BENGKULU' => 'BKL', 'LAMPUNG' => 'LPG',
            'KEPULAUAN BANGKA BELITUNG' => 'BBL', 'BANGKA BELITUNG' => 'BBL',
            'DKI JAKARTA' => 'JKT', 'JAKARTA' => 'JKT', 'BANTEN' => 'BTN',
            'JAWA BARAT' => 'JBR', 'JAWA TENGAH' => 'JTG', 'JAWA TIMUR' => 'JTM',
            'DI YOGYAKARTA' => 'DIY', 'DAERAH ISTIMEWA YOGYAKARTA' => 'DIY', 'YOGYAKARTA' => 'DIY',
            'BALI' => 'BLI', 'NUSA TENGGARA BARAT' => 'NTB', 'NUSA TENGGARA TIMUR' => 'NTT',
            'KALIMANTAN BARAT' => 'KBR', 'KALIMANTAN TENGAH' => 'KTG',
            'KALIMANTAN SELATAN' => 'KSL', 'KALIMANTAN TIMUR' => 'KTM', 'KALIMANTAN UTARA' => 'KUT',
            'SULAWESI UTARA' => 'SUT', 'SULAWESI TENGAH' => 'STG', 'SULAWESI SELATAN' => 'SSL',
            'SULAWESI TENGGARA' => 'STR', 'SULAWESI BARAT' => 'SBR', 'GORONTALO' => 'GTL',
            'MALUKU' => 'MLK', 'MALUKU UTARA' => 'MLU', 'PAPUA' => 'PPA',
            'PAPUA BARAT' => 'PBR', 'PAPUA BARAT DAYA' => 'PBD', 'PAPUA TENGAH' => 'PTG',
            'PAPUA PEGUNUNGAN' => 'PPG', 'PAPUA SELATAN' => 'PSL',
        ][$name] ?? str_pad(substr(preg_replace('/[^A-Z]/', '', $name), 0, 3), 3, 'X');
    }

    protected $fillable = [
        'code',
        'name',
        'leader_name',
        'sk_number',
        'sk_expiry_date',
        'sk_document_path',
    ];
    // app/Models/Province.php
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function officials()
    {
        // Relasi ke tabel officials berdasarkan province_id
        return $this->hasMany(Official::class, 'province_id');
    }

    // Relasi "Has Many Through" jika ingin langsung akses dojo dari provinsi
    public function dojos()
    {
        return $this->hasManyThrough(Dojo::class, City::class);
    }
}
