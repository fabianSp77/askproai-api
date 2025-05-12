@extends('layouts.admin')
@section('title', 'Willkommen bei AskProAI')
@section('content')
<div class="text-center my-5">
    <h1 class="display-4 mb-4">AskProAI</h1>
    <p class="lead mb-5">KI-gestützte Telefonassistenz für Praxen und Dienstleister</p>
    
    <div class="row justify-content-center mt-5">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Dashboard</h5>
                    <p class="card-text">Anrufe und Statistiken</p>
                    <a href="/dashboard" class="btn btn-primary">Öffnen</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Premium-Services</h5>
                    <p class="card-text">Dienste verwalten</p>
                    <a href="/admin/premium-services" class="btn btn-primary">Verwalten</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">FAQs</h5>
                    <p class="card-text">Häufige Fragen</p>
                    <a href="/admin/faqs" class="btn btn-primary">Bearbeiten</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
