<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{City, District, Province};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $actor = $request->user();
        $provinces = Province::when(!\App\Services\RoleAccess::managesRegionData($actor), fn ($q) => $q->whereKey($actor->province_id ?? 0))->orderBy('name')->get();
        $baseCities = City::whereIn('province_id', $provinces->modelKeys());
        $cityCount = (clone $baseCities)->count();
        $districtCount = District::whereIn('city_id', (clone $baseCities)->select('id'))->count();
        $citiesQuery = (clone $baseCities)->with(['province', 'districts'])->orderBy('name');
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $citiesQuery->where(fn ($query) => $query->where('name', 'like', $term)
                ->orWhereHas('province', fn ($province) => $province->where('name', 'like', $term))
                ->orWhereHas('districts', fn ($district) => $district->where('name', 'like', $term)));
        }
        $cities = \App\Support\PageSize::paginate($citiesQuery, $request, 15);
        $cityOptions = City::with('province')->whereIn('province_id', $provinces->modelKeys())->orderBy('name')->get();

        return view('admin.regions.index', compact('provinces', 'cities', 'cityOptions', 'cityCount', 'districtCount', 'filters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['type' => 'required|in:city,district', 'name' => 'required|string|max:150',
            'province_id' => 'required_if:type,city|nullable|exists:provinces,id',
            'city_id' => 'required_if:type,district|nullable|exists:cities,id']);
        $province = $data['type'] === 'city' ? (int) $data['province_id'] : (int) City::findOrFail($data['city_id'])->province_id;
        abort_unless(\App\Services\RoleAccess::managesRegionData($request->user()) || ($request->user()->province_id && (int) $request->user()->province_id === $province), 403);
        $table = $data['type'] === 'city' ? 'cities' : 'districts';
        $parent = $data['type'] === 'city' ? 'province_id' : 'city_id';
        $request->validate(['name' => [Rule::unique($table)->where($parent, $data[$parent])]]);
        ($data['type'] === 'city' ? City::class : District::class)::create([$parent => $data[$parent], 'name' => trim($data['name'])]);
        return back()->with('success', 'Wilayah berhasil ditambahkan.');
    }
}
