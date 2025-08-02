@extends('portal.layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold mb-4">Neuer Termin</h2>
                
                <div class="alert alert-info">
                    <p>Die Terminbuchung ist momentan nur über Telefon möglich.</p>
                    <p class="mt-2">Bitte rufen Sie einen Kunden an oder nutzen Sie die automatische Anruffunktion.</p>
                </div>
                
                <div class="mt-6">
                    <a href="{{ route('business.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Zurück zur Übersicht
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection