@extends('layouts.admin')
@section('title', 'FAQ erstellen')
@section('content')
    <h1>FAQ erstellen</h1>
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary mb-3">Zurück</a>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.faqs.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="question">Frage</label>
                    <input type="text" class="form-control" id="question" name="question" required>
                </div>
                <div class="mb-3">
                    <label for="answer">Antwort</label>
                    <textarea class="form-control" id="answer" name="answer" rows="4" required></textarea>
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
