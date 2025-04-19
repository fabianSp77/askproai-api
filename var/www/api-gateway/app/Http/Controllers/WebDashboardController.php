<?php

namespace App\Http\Controllers;

class WebDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }
}
