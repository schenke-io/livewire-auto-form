<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Workbench\App\Models\City;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::query()->with(['country'])->paginate(15);

        return view('cities', [
            'title' => 'Cities',
            'cities' => $cities,
        ]);
    }

    public function show(City $city)
    {
        // Eager-load relations
        $city->load(['country']);

        return view('city', [
            'title' => $city->name,
            'city' => $city,
        ]);
    }
}
