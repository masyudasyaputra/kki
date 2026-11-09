<?php

use App\Services\BeltCatalog;
use App\Models\BeltLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach (BeltLevel::whereRaw('LOWER(kyu_dan) LIKE ?', ['%kyu%'])->get() as $belt) {
            if (!app(BeltCatalog::class)->legacyTarget($belt)) throw new RuntimeException('Padanan sabuk belum tersedia: '.$belt->name);
        }
        Schema::table('belt_levels', fn (Blueprint $t) => $t->boolean('is_active')->default(true)->index());
        DB::transaction(function () {
            $catalog = app(BeltCatalog::class);
            $catalog->syncKyu();
            $targets = BeltLevel::active()->whereRaw('LOWER(kyu_dan) LIKE ?', ['%kyu%'])->get()->keyBy('name');
            $legacy = BeltLevel::where('is_active', false)->whereRaw('LOWER(kyu_dan) LIKE ?', ['%kyu%'])->orderBy('order')->get();
            foreach ($legacy as $old) {
                $name = $catalog->legacyTarget($old);
                if (!$name) throw new RuntimeException('Padanan sabuk lama belum tersedia: '.$old->name.' '.$old->kyu_dan);
                $target = $targets[$name];
                DB::table('users')->where('belt_level_id', $old->id)->update(['belt_level_id' => $target->id]);
                if (Schema::hasColumn('users', 'current_belt_level_id')) DB::table('users')->where('current_belt_level_id', $old->id)->update(['current_belt_level_id' => $target->id]);
            }
            // Reuse the existing fee rules for the representative color, without changing past payments.
            $sources = ['Putih' => 'Kyu 10', 'Kuning' => 'Kyu 9', 'Hijau' => 'Kyu 6', 'Biru Strip' => 'Kyu 5',
                'Biru Tua' => 'Kyu 4', 'Coklat Strip' => 'Kyu 2', 'Coklat Polos/Tua' => 'Kyu 1'];
            foreach ($sources as $name => $rank) {
                $old = $legacy->first(fn ($belt) => strcasecmp($belt->kyu_dan, $rank) === 0);
                if (!$old) continue;
                $target = $targets[$name];
                foreach (['fee_configurations', 'exam_fees'] as $table) {
                    if (!Schema::hasTable($table)) continue;
                    foreach (DB::table($table)->where('belt_level_id', $old->id)->get() as $fee) {
                        $key = ['belt_level_id' => $target->id];
                        if ($table === 'fee_configurations') $key['province_id'] = $fee->province_id;
                        if (!DB::table($table)->where($key)->exists()) DB::table($table)->insert($key + ['amount' => $fee->amount, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Keep referenced ranks; removing the flag restores the complete historic catalog.
        Schema::table('belt_levels', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
