<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamReview extends Model
{
    protected $fillable = ['exam_id', 'actor_id', 'status', 'notes', 'snapshot'];
    protected $casts = ['snapshot' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
