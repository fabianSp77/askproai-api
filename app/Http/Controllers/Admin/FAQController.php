<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;

class FAQController extends Controller
{
    public function index()
    {
        $faqs = FAQ::all();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'active' => 'boolean',
        ]);

        FAQ::create($validated);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ erstellt');
    }

    public function edit(FAQ $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, FAQ $faq)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'active' => 'boolean',
        ]);

        $faq->update($validated);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ aktualisiert');
    }

    public function destroy(FAQ $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ gelöscht');
    }
}
