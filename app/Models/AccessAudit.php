<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessAudit extends Model
{
    protected $fillable = ['actor_id', 'target_id', 'event', 'before', 'after', 'exam_id'];
    protected $casts = ['before' => 'array', 'after' => 'array'];

    public static function record(string $event, ?User $actor, ?User $target, array $before, array $after, ?int $examId = null): void
    {
        self::create(['event' => $event, 'actor_id' => $actor?->id, 'target_id' => $target?->id,
            'before' => $before, 'after' => $after, 'exam_id' => $examId]);
    }
}
