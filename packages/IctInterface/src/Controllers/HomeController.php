<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Http\Request;
use Packages\IctInterface\Controllers\IctController;

class HomeController extends IctController
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
        
        return view('ict::home');
    }
}
