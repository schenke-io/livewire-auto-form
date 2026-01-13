<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Workbench\App\Models\Language;

class LanguageController
{
    public function index(Request $request): View
    {
        $languages = Language::query()->orderBy('code')->paginate(15);

        return view('languages.index', compact('languages'));
    }

    public function show(Language $language): View
    {
        $language->load(['countries']);

        return view('languages.show', [
            'language' => $language,
            'countries' => $language->countries()->orderBy('name')->paginate(15),
        ]);
    }
}
