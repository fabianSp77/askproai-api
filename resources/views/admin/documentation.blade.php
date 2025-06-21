@extends('portal.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Dokumentation') }}</div>

                <div class="card-body">
                    <h2>AskProAI Dokumentation</h2>
                    <p>Hier finden Sie die vollständige Dokumentation des AskProAI-Projekts.</p>
                    
                    <div class="mt-4">
                        <h3>Verfügbare Dokumentation:</h3>
                        <ul class="list-group mt-3">
                            <li class="list-group-item">
                                <a href="{{ url('/admin/documentation') }}" target="_blank">
                                    Implementierungsdokumentation öffnen
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="{{ asset('admin/documentation/assets/askproai-implementation-report.md') }}" target="_blank">
                                    Vollständiger Implementierungsbericht (Markdown)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
