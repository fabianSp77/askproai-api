<?php

namespace App\Http\Controllers;

use App\Models\FAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = FAQ::where('active', true)->orderBy('category')->get();
        $categories = DB::table('faqs')->select('category')->distinct()->get();
        return view('faqs.index', compact('faqs', 'categories'));
    }

    public function create()
    {
        return view('faqs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'category' => 'required'
        ]);

        FAQ::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'active' => true
        ]);

        return redirect()->route('faqs.index')
            ->with('success', 'FAQ erfolgreich erstellt');
    }
}
