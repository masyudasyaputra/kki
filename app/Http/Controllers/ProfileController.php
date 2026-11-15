<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['province', 'city', 'dojo', 'beltLevel']);
        $provinceId = old('province_id', $user->province_id);
        $cityId = old('city_id', $user->city_id);
        return view('profile.edit', [
            'user' => $user,
            'provinces' => \App\Models\Province::orderBy('name')->get(['id', 'name']),
            'cities' => $provinceId ? \App\Models\City::where('province_id', $provinceId)->orderBy('name')->get(['id', 'name']) : collect(),
            'districts' => $cityId ? \App\Models\District::where('city_id', $cityId)->orderBy('name')->get(['id', 'name']) : collect(),
            'dojos' => $cityId ? \App\Models\Dojo::where('city_id', $cityId)->orderBy('name')->get(['id', 'name']) : collect(),
            'beltLevels' => \App\Models\BeltLevel::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $user->belt_level_id))->orderBy('order')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except(['photo', 'belt_level_id', 'belt_diploma']));
        $newDiploma = null;
        if ($request->user()->role === 'member' && $request->hasFile('belt_diploma')) {
            $newDiploma = $request->file('belt_diploma')->store('belt-diplomas/'.$user->id, 'local');
            $user->forceFill(['requested_belt_level_id' => $request->input('belt_level_id', $user->requested_belt_level_id ?? $user->belt_level_id),
                'belt_diploma_path' => $newDiploma, 'verification_status' => 'pending']);
        }
        $previousPhoto = $user->photo;
        $newPhoto = null;
        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('member-photos/'.$user->id, 'public');
            if (!$newPhoto) {
                throw ValidationException::withMessages(['photo' => 'Pasfoto belum dapat disimpan. Silakan coba lagi.']);
            }
            $user->photo = $newPhoto;
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        try {
            $user->save();
        } catch (\Throwable $exception) {
            if ($newDiploma) Storage::disk('local')->delete($newDiploma);
            if ($newPhoto) Storage::disk('public')->delete($newPhoto);
            throw $exception;
        }
        if ($newPhoto && $previousPhoto && str_starts_with($previousPhoto, 'member-photos/'.$user->id.'/')) {
            Storage::disk('public')->delete($previousPhoto);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function beltDiploma(Request $request, \App\Models\User $member)
    {
        if ($request->user()->id !== $member->id) {
            abort_unless(app(\App\Services\MembershipReview::class)->members($request->user())->whereKey($member->id)->exists(), 403);
        }
        abort_unless($member->belt_diploma_path && Storage::disk('local')->exists($member->belt_diploma_path), 404);
        return response()->file(Storage::disk('local')->path($member->belt_diploma_path), ['X-Content-Type-Options' => 'nosniff']);
    }
}
