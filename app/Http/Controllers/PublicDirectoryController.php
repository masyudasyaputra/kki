<?php

namespace App\Http\Controllers;

use App\Models\BeltLevel;
use App\Models\City;
use App\Models\Dojo;
use App\Models\Exam;
use App\Models\Province;
use App\Models\User;
use App\Support\PageSize;
use Illuminate\Http\Request;

class PublicDirectoryController extends Controller
{
    public function agenda(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
        ]);

        $query = Exam::query()
            ->with('province')
            ->withCount('participants')
            ->whereDate('execution_date', '>=', today())
            ->whereIn('status', ['open', 'ongoing']);

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }
        if (!empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)
                ->orWhere('location', 'like', $term)
                ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term)));
        }

        $agendas = PageSize::paginate($query->orderBy('execution_date')->orderBy('name'), $request, 12);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);

        return view('public.agenda', compact('agendas', 'provinces', 'filters'));
    }

    public function dojos(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        $query = Dojo::query()
            ->with(['city.province'])
            ->where('is_active', true)
            ->whereDate('sk_expiry_date', '>=', today());

        if (!empty($filters['province_id'])) {
            $query->whereHas('city', fn ($city) => $city->where('province_id', $filters['province_id']));
        }
        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }
        if (!empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)
                ->orWhere('address', 'like', $term)
                ->orWhere('sensei_name', 'like', $term)
                ->orWhereHas('city', fn ($city) => $city->where('name', 'like', $term)
                    ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term))));
        }

        $dojos = PageSize::paginate($query->orderBy('name'), $request, 18);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $cities = City::query()
            ->when(!empty($filters['province_id']), fn ($city) => $city->where('province_id', $filters['province_id']))
            ->orderBy('name')->get(['id', 'name', 'province_id']);

        return view('public.dojos', compact('dojos', 'provinces', 'cities', 'filters'));
    }

    public function members(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'belt_level_id' => ['nullable', 'integer', 'exists:belt_levels,id'],
        ]);

        $query = User::query()
            ->where('role', 'member')
            ->where('verification_status', 'approved')
            ->where('is_active', true)
            ->with(['province', 'city', 'dojo', 'beltLevel']);

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }
        if (!empty($filters['belt_level_id'])) {
            $query->where('belt_level_id', $filters['belt_level_id']);
        }
        if (!empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)
                ->orWhereHas('dojo', fn ($dojo) => $dojo->where('name', 'like', $term))
                ->orWhereHas('city', fn ($city) => $city->where('name', 'like', $term))
                ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term))
                ->orWhereHas('beltLevel', fn ($belt) => $belt->where('name', 'like', $term)));
        }

        $members = PageSize::paginate($query->orderBy('name'), $request, 24);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $beltLevels = BeltLevel::query()->orderBy('order')->get(['id', 'name', 'kyu_dan']);

        return view('public.members', compact('members', 'provinces', 'beltLevels', 'filters'));
    }
}
