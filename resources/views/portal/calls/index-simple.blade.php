@extends('portal.simple-layout')

@section('title', 'Anrufe')

@section('content')
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Anrufe</h1>
        
        <p class="text-gray-600 mb-4">Hier sehen Sie alle eingehenden Anrufe.</p>
        
        @php
            $calls = \App\Models\Call::where('company_id', Auth::guard('portal')->user()->company_id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        @endphp
        
        @if($calls->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Telefonnummer</th>
                        <th class="text-left py-2">Datum</th>
                        <th class="text-left py-2">Dauer</th>
                        <th class="text-left py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls as $call)
                        <tr class="border-b">
                            <td class="py-2">{{ $call->phone_number ?? 'Unbekannt' }}</td>
                            <td class="py-2">{{ $call->created_at->format('d.m.Y H:i') }}</td>
                            <td class="py-2">{{ $call->duration_sec ?? 0 }}s</td>
                            <td class="py-2">{{ $call->status ?? 'Neu' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500">Keine Anrufe vorhanden.</p>
        @endif
    </div>
@endsection