@extends('layouts.app')
@section('title', 'FAQ erstellen - AskProAI')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Neue FAQ erstellen</h1>
    <a href="{{ route('faqs.index') }}" class="btn btn-secondary">Zurück</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('faqs.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="category" class="form-label">Kategorie</label>
                <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" required>
                @error('category')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="question" class="form-label">Frage</label>
                <input type="text" class="form-control @error('question') is-invalid @enderror" id="question" name="question" value="{{ old('question') }}" required>
                @error('question')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="answer" class="form-label">Antwort</label>
                <textarea class="form-control @error('answer') is-invalid @enderror" id="answer" name="answer" rows="6" required>{{ old('answer') }}</textarea>
                @error('answer')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
    </div>
</div>
@endsection
