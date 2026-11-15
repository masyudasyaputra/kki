<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialQualification extends Model
{
    public const KINDS = ['referee' => 'Wasit', 'judge' => 'Juri', 'both' => 'Wasit & Juri'];
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date', 'reviewed_at' => 'datetime'];
    }
    public function user() { return $this->belongsTo(User::class); }
    public function province() { return $this->belongsTo(Province::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function getIsValidAttribute(): bool
    {
        return $this->status === 'approved' && (!$this->expires_at || $this->expires_at->gte(today()));
    }
}
