<?php

namespace App\Services;

use App\Models\{Official, AccessAudit, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class OfficialNumbers
{
    public function automatic(Official $official): string
    {
        return 'PGR-'.($official->province?->member_prefix ?? 'UNK').'-'.str_pad((string) ($official->user_id ?: $official->id), 6, '0', STR_PAD_LEFT);
    }

    public function issue(Official $official): void
    {
        if ($official->official_number || !$official->getConnection()->getSchemaBuilder()->hasColumn('officials', 'official_number')) return;
        $base = $this->automatic($official);
        $split = strrpos($base, '-');
        $prefix = substr($base, 0, $split + 1);
        $sequence = (int) substr($base, $split + 1);
        for ($attempt = 0; $attempt < 1000; $attempt++, $sequence++) {
            try {
                DB::transaction(function () use ($official, $prefix, $sequence) {
                    $locked = Official::whereKey($official->id)->lockForUpdate()->firstOrFail();
                    if (!$locked->official_number) $locked->forceFill(['official_number' => $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT)])->saveQuietly();
                    $official->setRawAttributes($locked->getAttributes(), true);
                });
                return;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 999) throw $exception;
            }
        }
    }

    public function change(Official $official, User $actor, array $data): void
    {
        try {
            DB::transaction(function () use ($official, $actor, $data) {
                $locked = Official::whereKey($official->id)->lockForUpdate()->firstOrFail();
                if ($locked->official_number !== $data['expected_number']) throw ValidationException::withMessages(['number' => 'Nomor sudah berubah. Muat ulang halaman.']);
                $number = $data['mode'] === 'auto' ? $this->automatic($locked) : $data['number'];
                if (Official::where('official_number', $number)->whereKeyNot($locked->id)->exists()) throw ValidationException::withMessages(['number' => 'Nomor ID pengurus sudah digunakan.']);
                $before = $locked->only(['official_number', 'official_number_manual']);
                $locked->forceFill(['official_number' => $number, 'official_number_manual' => $data['mode'] === 'manual'])->save();
                AccessAudit::record('official.number.updated', $actor, $locked->user, $before + ['official_id' => $locked->id],
                    $locked->only(['official_number', 'official_number_manual']) + ['official_id' => $locked->id, 'reason' => $data['reason']]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['number' => 'Nomor ID pengurus sudah digunakan.']);
        }
    }
}
