<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Workbench\App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::query()->with('city.country')->paginate(15);

        return view('brands', [
            'title' => 'Brands',
            'brands' => $brands,
        ]);
    }

    public function show(Brand $brand)
    {
        $brand->load(['city.country']);
        $otherBrands = $brand->city
            ? $brand->city->brands()->where('id', '!=', $brand->id)->orderBy('name')->paginate(15, ['*'], 'other_brands_page')
            : collect();

        return view('brand', [
            'title' => $brand->name,
            'brand' => $brand,
            'otherBrands' => $otherBrands,
        ]);
    }
}
