<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Workbench\App\Models\City;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::query()->with(['country', 'rivers'])->paginate(15);

        return view('cities', [
            'title' => 'Cities',
            'cities' => $cities,
        ]);
    }

    public function show(City $city)
    {
        // Eager-load relations
        $city->load(['country']);

        // Paginate related data (15 per page)
        $rivers = $city->rivers()
            ->withPivot(['bridge_count'])
            ->orderByDesc('length_km')
            ->paginate(15, ['*'], 'rivers_page');

        $brands = $city->brands()
            ->orderBy('name')
            ->paginate(15, ['*'], 'brands_page');

        return view('city', [
            'title' => $city->name,
            'city' => $city,
            'rivers' => $rivers,
            'brands' => $brands,
        ]);
    }
}
