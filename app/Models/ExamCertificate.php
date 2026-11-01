<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamCertificate extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['snapshot' => 'array', 'previous_versions' => 'array', 'sent_at' => 'datetime'];

    public function participant()
    {
        return $this->belongsTo(ExamParticipant::class, 'exam_participant_id');
    }
}
