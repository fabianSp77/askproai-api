@extends("portal.simple-layout")

@section("title", "Dashboard")

@section("content")
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Business Portal Dashboard</h2>
            <p class="text-gray-600 mb-4">Willkommen in Ihrem Business Portal\!</p>
            
            <div class="bg-green-100 p-4 rounded mb-4">
                <p class="font-semibold">✅ Sie sind angemeldet\!</p>
                <p>Benutzer ID: {{ Auth::guard("portal")->user()->id }}</p>
                <p>E-Mail: {{ Auth::guard("portal")->user()->email }}</p>
                <p>Firma ID: {{ Auth::guard("portal")->user()->company_id }}</p>
            </div>
        </div>
    </div>
@endsection
