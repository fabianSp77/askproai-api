<?php

namespace App\Http\Controllers;

use App\Models\FAQ;

class FAQController extends Controller
{
    public function index()
    {
        $faqs = FAQ::where('active', true)->get();

        return view('faqs.index', compact('faqs'));
    }
}
