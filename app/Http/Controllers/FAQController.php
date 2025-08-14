<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FAQController extends Controller
{
    public function index()
    {
        $faqs = Faq::where('active', true)->get();

        return view('faqs.index', compact('faqs'));
    }
}
