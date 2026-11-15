<?php

namespace App\Support;

class MemberCardTheme
{
    public static function forBelt(?string $name): array
    {
        $name = mb_strtolower($name ?? '');
        $colors = [
            'putih' => ['#ffffff', '#f4f1ea', '#e4ded2', '#25221c', '#655e51', '#786039'],
            'kuning' => ['#ffe990', '#edc52d', '#d4a715', '#302707', '#66500c', '#685019'],
            'hijau' => ['#297b4e', '#145c36', '#0c3722'],
            'biru tua' => ['#234a83', '#10284f', '#091b36'],
            'biru' => ['#3869ac', '#163d80', '#0e2856'],
            'cokelat' => ['#966a43', '#583621', '#352014'],
            'coklat' => ['#966a43', '#583621', '#352014'],
            'hitam' => ['#27282b', '#111214', '#070809'],
            'orange' => ['#db7b35', '#a94512', '#71300e'],
            'oranye' => ['#db7b35', '#a94512', '#71300e'],
            'jingga' => ['#db7b35', '#a94512', '#71300e'],
            'ungu' => ['#8452ab', '#512777', '#32174e'],
            'merah' => ['#bf4653', '#821f2b', '#4f121c'],
        ];
        $palette = ['#762137', '#481522', '#2e1019'];
        foreach ($colors as $color => $values) {
            if (str_contains($name, $color)) { $palette = $values; break; }
        }
        return ['start' => $palette[0], 'base' => $palette[1], 'end' => $palette[2],
            'ink' => $palette[3] ?? '#f7f4ed', 'muted' => $palette[4] ?? '#ddd5c7',
            'accent' => $palette[5] ?? '#e6c984', 'stripe' => str_contains($name, 'strip')];
    }
}
