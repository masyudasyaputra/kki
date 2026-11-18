<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequest extends Model
{
    public const KINDS = [
        'access' => 'Akses / salinan data', 'correction' => 'Koreksi data',
        'deletion' => 'Penghapusan / pengakhiran pemrosesan',
        'restriction' => 'Pembatasan / keberatan pemrosesan',
        'withdrawal' => 'Penarikan persetujuan', 'complaint' => 'Pengaduan privasi',
    ];
    public const STATUSES = ['pending' => 'Menunggu', 'processing' => 'Dalam penanganan', 'completed' => 'Selesai', 'rejected' => 'Ditolak dengan alasan'];
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['details' => 'encrypted', 'response' => 'encrypted', 'responded_at' => 'datetime'];
    }
    public function user() { return $this->belongsTo(User::class); }

    public function getDeadlineAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return in_array($this->kind, ['access', 'correction', 'restriction', 'withdrawal'], true)
            ? $this->created_at?->copy()->addHours(72) : null;
    }
}
