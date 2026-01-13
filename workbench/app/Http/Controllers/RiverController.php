<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Workbench\App\Models\River;

class RiverController extends Controller
{
    public function index()
    {
        $rivers = River::query()->with('cities.country')->paginate(15);

        // Prepare per-river top 3 largest cities and list of countries
        $topCities = [];
        $countries = [];
        foreach ($rivers as $r) {
            $topCities[$r->id] = $r->cities->sortByDesc('population')->take(3);
            $countries[$r->id] = $r->cities->pluck('country')->filter()->unique('id');
        }

        return view('rivers', [
            'title' => 'Rivers',
            'rivers' => $rivers,
            'topCities' => $topCities,
            'countries' => $countries,
        ]);
    }

    public function show(River $river)
    {
        // Eager-load
        $river->load('cities.country');

        // Paginated cities with pivot info
        $cities = $river->cities()
            ->withPivot(['bridge_count'])
            ->orderByDesc('population')
            ->paginate(15, ['*'], 'cities_page');

        // Countries involved (unique list)
        $countries = $river->cities->pluck('country')->filter()->unique('id');

        return view('river', [
            'title' => $river->name,
            'river' => $river,
            'countries' => $countries,
            'cities' => $cities,
        ]);
    }
}
