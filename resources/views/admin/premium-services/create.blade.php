@extends('layouts.admin')
@section('title', 'Service erstellen')
@section('content')
    <h1>Neuen Premium-Service erstellen</h1>
    <a href="{{ route('admin.premium-services.index') }}" class="btn btn-secondary mb-3">Zurück</a>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.premium-services.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="description">Beschreibung</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="price">Preis (€)</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="duration">Dauer</label>
                    <input type="text" class="form-control" id="duration" name="duration">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" checked>
                    <label class="form-check-label" for="active">Aktiv</label>
                </div>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </form>
        </div>
    </div>
@endsection
