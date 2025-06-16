<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = DB::table('faqs')->orderBy('category')->get();
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

        DB::table('faqs')->insert([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('faqs.index')
            ->with('success', 'FAQ erfolgreich erstellt');
    }
}
