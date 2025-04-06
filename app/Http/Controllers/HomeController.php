<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $file = storage_path('crud_models.json');
        $models = [];

        if (file_exists($file)) {
            $models = json_decode(file_get_contents($file), true);
        }
        return view('home', compact('models'));
    }
}
