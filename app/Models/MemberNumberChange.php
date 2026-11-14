<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberNumberChange extends Model
{
    protected $fillable = ['member_id', 'actor_id', 'before_number', 'after_number', 'mode', 'reason'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
