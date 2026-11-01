<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipVerification extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
