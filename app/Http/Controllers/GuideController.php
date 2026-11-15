<?php

namespace App\Http\Controllers;

class GuideController extends Controller
{
    public function __invoke()
    {
        $sections = config('guide.sections');
        $guideIndex = collect($sections)->map(function ($section, $id) {
            $parts = [];
            array_walk_recursive($section, function ($value) use (&$parts) { if (is_string($value)) $parts[] = $value; });
            return ['id' => $id, 'title' => $section['title'], 'audiences' => $section['audiences'], 'text' => mb_strtolower(implode(' ', $parts))];
        })->values()->all();
        return view('guide', compact('sections', 'guideIndex'));
    }
}
