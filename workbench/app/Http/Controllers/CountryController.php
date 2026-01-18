<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Workbench\App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::query()->with('cities')->paginate(15);

        // Compute top lists per country
        $topCities = [];
        foreach ($countries as $country) {
            $topCities[$country->id] = $country->cities()
                ->orderByDesc('population')
                ->take(3)
                ->get();
        }

        return view('countries', [
            'title' => 'Countries',
            'countries' => $countries,
            'topCities' => $topCities,
        ]);
    }

    public function show(Country $country)
    {
        // Eager-load immediate relations
        $country->load(['cities']);

        // Paginate relations (15 per page) and include pivot fields where applicable
        $cities = $country->cities()->orderByDesc('population')->paginate(15, ['*'], 'cities_page');
        $borders = $country->borders()->withPivot(['border_length_km'])->orderBy('name')->paginate(15, ['*'], 'borders_page');

        return view('country', [
            'title' => $country->name,
            'country' => $country,
            'cities' => $cities,
            'borders' => $borders,
        ]);
    }
}
