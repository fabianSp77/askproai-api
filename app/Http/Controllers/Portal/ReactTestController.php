<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;

class ReactTestController extends Controller
{
    public function index()
    {
        return view('portal.react-test');
    }
}