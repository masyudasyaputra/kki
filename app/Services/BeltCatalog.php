<?php

namespace App\Services;

use App\Models\BeltLevel;

class BeltCatalog
{
    public const KYU = [
        ['name' => 'Putih', 'kyu_dan' => 'Kyu 7', 'order' => 1, 'membership_fee' => 50000],
        ['name' => 'Kuning', 'kyu_dan' => 'Kyu 6', 'order' => 2, 'membership_fee' => 60000],
        ['name' => 'Hijau', 'kyu_dan' => 'Kyu 5', 'order' => 3, 'membership_fee' => 75000],
        ['name' => 'Biru Strip', 'kyu_dan' => 'Kyu 4', 'order' => 4, 'membership_fee' => 90000],
        ['name' => 'Biru Tua', 'kyu_dan' => 'Kyu 3', 'order' => 5, 'membership_fee' => 100000],
        ['name' => 'Coklat Strip', 'kyu_dan' => 'Kyu 2', 'order' => 6, 'membership_fee' => 110000],
        ['name' => 'Coklat Polos/Tua', 'kyu_dan' => 'Kyu 1', 'order' => 7, 'membership_fee' => 110000],
    ];

    public const DAN = [
        ['name' => 'Hitam', 'kyu_dan' => 'DAN I', 'order' => 8, 'membership_fee' => 150000],
        ['name' => 'Hitam', 'kyu_dan' => 'DAN II', 'order' => 9, 'membership_fee' => 150000],
        ['name' => 'Hitam', 'kyu_dan' => 'DAN III', 'order' => 10, 'membership_fee' => 200000],
        ['name' => 'Hitam', 'kyu_dan' => 'DAN IV', 'order' => 11, 'membership_fee' => 250000],
        ['name' => 'Hitam', 'kyu_dan' => 'DAN V', 'order' => 12, 'membership_fee' => 300000],
        ['name' => 'Hitam', 'kyu_dan' => 'DAN VI', 'order' => 13, 'membership_fee' => 500000],
    ];

    public static function defaults(): array
    {
        return [...self::KYU, ...self::DAN];
    }

    public function syncKyu(): void
    {
        BeltLevel::whereRaw('LOWER(kyu_dan) LIKE ?', ['%kyu%'])->update(['is_active' => false]);
        foreach (self::KYU as $attributes) {
            $belt = BeltLevel::firstOrCreate(['name' => $attributes['name'], 'kyu_dan' => $attributes['kyu_dan']], $attributes);
            $belt->update(['order' => $attributes['order'], 'is_active' => true]);
        }
    }

    public function legacyTarget(BeltLevel $belt): ?string
    {
        $name = strtolower(trim($belt->name));
        if (str_contains($name, 'putih')) return 'Putih';
        if (str_contains($name, 'kuning') || str_contains($name, 'orange') || str_contains($name, 'jingga')) return 'Kuning';
        if (str_contains($name, 'hijau')) return 'Hijau';
        if (str_contains($name, 'ungu')) return 'Biru Tua';
        if (str_contains($name, 'biru')) return str_contains($name, 'tua') ? 'Biru Tua' : 'Biru Strip';
        if (str_contains($name, 'cokelat') || str_contains($name, 'coklat')) {
            return preg_match('/kyu\s*1\b/i', $belt->kyu_dan) ? 'Coklat Polos/Tua' : 'Coklat Strip';
        }
        return null;
    }

    public function next(?BeltLevel $current, string $examType): ?BeltLevel
    {
        if (!$current) return null;
        // Legacy ranks need an explicit correction before an automatic promotion can be selected.
        if (!$current->is_active) return null;
        if ($examType === 'dan' && stripos($current->kyu_dan, 'kyu') !== false && !preg_match('/kyu\s*1\b/i', $current->kyu_dan)) return null;
        return BeltLevel::active()->where('kyu_dan', 'like', '%'.$examType.'%')
            ->where('order', '>', $current->order)->orderBy('order')->first();
    }
}
