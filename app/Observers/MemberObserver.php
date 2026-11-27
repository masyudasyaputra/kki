<?php

namespace App\Observers;

use App\Models\User;
use App\Models\FeeConfiguration; // Tambahkan ini agar tidak error
use Illuminate\Support\Facades\Log;

class MemberObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        app(\App\Services\MemberNumbers::class)->issue($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user)
    {
        if ($user->wasChanged('province_id')) app(\App\Services\MemberNumbers::class)->repairUnknownRegion($user);
        if ($user->wasChanged(['belt_level_id']) && $user->wasChanged('member_number_override') &&
            preg_match('/(^|-)MSH(-|$)/i', $user->getRawOriginal('member_number_override') ?? '') &&
            !$user->isBlackBelt() && $user->getConnection()->getSchemaBuilder()->hasTable('member_number_changes')) {
            \App\Models\MemberNumberChange::create(['member_id' => $user->id, 'actor_id' => auth()->id(),
                'before_number' => $user->getRawOriginal('member_number_override'), 'after_number' => $user->member_number,
                'mode' => 'auto', 'reason' => 'Kode MSH dihapus karena koreksi tingkat sabuk menjadi non-hitam.']);
        }
        if ($user->wasChanged('role')) app(\App\Services\MemberNumbers::class)->issue($user);
        // Jika kolom belt_level_id berubah (artinya naik sabuk)
        if ($user->isDirty('belt_level_id')) {
            try {
                // 1. Cari harga iuran untuk sabuk baru ini
                // Mencari berdasarkan provinsi user, jika tidak ada pakai harga default (province_id null)
                $fee = FeeConfiguration::where('province_id', $user->province_id)
                    ->where('belt_level_id', $user->belt_level_id)
                    ->first() ?? FeeConfiguration::whereNull('province_id')
                        ->where('belt_level_id', $user->belt_level_id)
                        ->first();

                // 2. Buat record pembayaran PENDING baru otomatis jika konfigurasi biaya ditemukan
                if ($fee) {
                    $user->payments()->create([
                        'belt_level_id' => $user->belt_level_id,
                        'amount' => $fee->amount,
                        'status' => 'PENDING',
                        'description' => 'Iuran sabuk baru otomatis',
                        'created_at' => now(),
                    ]);

                    Log::info("Tagihan otomatis dibuat untuk User ID: {$user->id} karena naik sabuk.");
                }
            } catch (\Exception $e) {
                // Gunakan log agar jika error iuran gagal, proses naik sabuk di database tidak ikut hancur
                Log::error("Gagal membuat tagihan otomatis di MemberObserver: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
