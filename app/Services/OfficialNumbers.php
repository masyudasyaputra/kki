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
        $region = $official->level === 'pusat' ? 'PP' : ($official->province?->member_prefix ?? 'UNK');
        if ($official->level === 'pengcab') $region .= '-'.($official->city?->registration_code ?? 'UNK');
        $sequence = $official->registration_sequence ?? ($official->user_id ?: $official->id);
        $year = $official->sk_expiry_date ? \Carbon\Carbon::parse($official->sk_expiry_date)->format('Y') : '0000';
        return 'PGR-'.$region.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT).'-'.$year;
    }

    public function issue(Official $official): void
    {
        if ($official->official_number || !$official->getConnection()->getSchemaBuilder()->hasColumn('officials', 'official_number')) return;
        DB::transaction(function () use ($official) {
            $locked = Official::whereKey($official->id)->lockForUpdate()->firstOrFail();
            if ($locked->official_number) return;
            if ($locked->getConnection()->getSchemaBuilder()->hasColumn('officials', 'registration_sequence')) {
                $minimum = (int) Official::max('registration_sequence');
                do {
                    $locked->forceFill(['registration_sequence' => app(RegistrationSequence::class)->next('official', $minimum)]);
                } while (Official::where('official_number', $this->automatic($locked))->exists());
            }
            $locked->forceFill(['official_number' => $this->automatic($locked)])->saveQuietly();
            $official->setRawAttributes($locked->getAttributes(), true);
        });
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
