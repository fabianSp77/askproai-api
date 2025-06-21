@extends('layouts.public')

@section('title', 'Hilfe-Artikel')

@section('content')
<div class="help-article-container">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8">
        <div class="container mx-auto px-4">
            <!-- Breadcrumb -->
            <nav class="text-sm mb-4">
                @foreach($breadcrumbs as $breadcrumb)
                    @if($breadcrumb['url'])
                        <a href="{{ $breadcrumb['url'] }}" class="text-blue-200 hover:text-white">{{ $breadcrumb['name'] }}</a>
                        <span class="mx-2">›</span>
                    @else
                        <span class="text-white">{{ $breadcrumb['name'] }}</span>
                    @endif
                @endforeach
            </nav>
            
            <h1 class="text-3xl font-bold">{{ $title }}</h1>
        </div>
    </div>

    <!-- Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar -->
                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                        <h3 class="font-semibold text-lg mb-4">In diesem Bereich</h3>
                        <nav class="space-y-2">
                            <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'registration']) }}" 
                               class="block text-sm {{ $category == 'getting-started' && $topic == 'registration' ? 'text-blue-600 font-semibold' : 'text-gray-600 hover:text-blue-600' }}">
                                Anmeldung und Registrierung
                            </a>
                            <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" 
                               class="block text-sm {{ $category == 'getting-started' && $topic == 'first-call' ? 'text-blue-600 font-semibold' : 'text-gray-600 hover:text-blue-600' }}">
                                Ihr erstes Telefonat
                            </a>
                            <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'portal-overview']) }}" 
                               class="block text-sm {{ $category == 'getting-started' && $topic == 'portal-overview' ? 'text-blue-600 font-semibold' : 'text-gray-600 hover:text-blue-600' }}">
                                Das Kundenportal
                            </a>
                        </nav>

                        <hr class="my-6">

                        <h3 class="font-semibold text-lg mb-4">Brauchen Sie Hilfe?</h3>
                        <a href="#" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            Live-Chat starten
                        </a>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow">
                        <div class="prose prose-blue max-w-none p-8">
                            {!! $content !!}
                        </div>
                    </div>

                    <!-- Feedback -->
                    <div class="mt-8 bg-gray-50 rounded-lg p-6">
                        <h3 class="font-semibold text-lg mb-4">War dieser Artikel hilfreich?</h3>
                        <div class="flex space-x-4">
                            <button class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition">
                                👍 Ja
                            </button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition">
                                👎 Nein
                            </button>
                        </div>
                    </div>

                    <!-- Related Articles -->
                    <div class="mt-8">
                        <h3 class="font-semibold text-lg mb-4">Verwandte Artikel</h3>
                        <div class="space-y-3">
                            <a href="#" class="block bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                                <h4 class="font-medium text-blue-600">Passwort ändern</h4>
                                <p class="text-sm text-gray-600 mt-1">So ändern Sie Ihr Passwort sicher...</p>
                            </a>
                            <a href="#" class="block bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                                <h4 class="font-medium text-blue-600">Benachrichtigungen einstellen</h4>
                                <p class="text-sm text-gray-600 mt-1">Passen Sie Ihre Benachrichtigungen an...</p>
                            </a>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>

<style>
/* Prose customization for help articles */
.prose h2 {
    color: #1e40af;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h3 {
    color: #2563eb;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose ul {
    list-style-type: disc;
}

.prose ol {
    list-style-type: decimal;
}

.prose code {
    background-color: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.prose blockquote {
    border-left: 4px solid #3b82f6;
    padding-left: 1rem;
    font-style: italic;
    color: #4b5563;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
}

.prose table th {
    background-color: #f3f4f6;
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
}

.prose table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}
</style>
@endsection