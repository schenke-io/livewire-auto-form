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
        $topRivers = [];
        foreach ($countries as $country) {
            $topCities[$country->id] = $country->cities()
                ->orderByDesc('population')
                ->take(3)
                ->get();

            // Rivers via the country's cities (distinct by river id)
            $riverIds = \Workbench\App\Models\River::query()
                ->whereHas('cities', function ($q) use ($country) {
                    $q->where('country_id', $country->id);
                })
                ->orderByDesc('length_km')
                ->limit(3)
                ->pluck('id')
                ->all();
            $topRivers[$country->id] = \Workbench\App\Models\River::whereIn('id', $riverIds)
                ->orderByDesc('length_km')
                ->get();
        }

        return view('countries', [
            'title' => 'Countries',
            'countries' => $countries,
            'topCities' => $topCities,
            'topRivers' => $topRivers,
        ]);
    }

    public function show(Country $country)
    {
        // Eager-load immediate relations
        $country->load(['cities']);

        // Paginate relations (15 per page) and include pivot fields where applicable
        $cities = $country->cities()->orderByDesc('population')->paginate(15, ['*'], 'cities_page');
        $borders = $country->borders()->withPivot(['border_length_km'])->orderBy('name')->paginate(15, ['*'], 'borders_page');
        $languages = $country->languages()->orderBy('code')->paginate(15, ['*'], 'languages_page');

        return view('country', [
            'title' => $country->name,
            'country' => $country,
            'cities' => $cities,
            'borders' => $borders,
            'languages' => $languages,
        ]);
    }
}
