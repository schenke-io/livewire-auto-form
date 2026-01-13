<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return view('hello', [
            'title' => 'Hello World',
            'message' => 'Hello World from the Workbench! 🎉',
        ]);
    }
}
