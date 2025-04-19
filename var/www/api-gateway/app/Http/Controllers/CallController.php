<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CallController extends Controller
{
   public function index() {
       $calls = DB::table('calls')->orderBy('call_time', 'desc')->get();
       return view('calls.index', compact('calls'));
   }
   public function show($id) {
       $call = DB::table('calls')->find($id);
       return view('calls.show', compact('call'));
   }
}
