@extends('layouts.admin')
@section('title', 'FAQ bearbeiten')
@section('content')
    <h1>FAQ bearbeiten</h1>
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary mb-3">Zurück</a>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="question">Frage</label>
                    <input type="text" class="form-control" id="question" name="question" value="{{ $faq->question }}" required>
                </div>
                <div class="mb-3">
                    <label for="answer">Antwort</label>
                    <textarea class="form-control" id="answer" name="answer" rows="4" required>{{ $faq->answer }}</textarea>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ $faq->active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Aktiv</label>
                </div>
                <button type="submit" class="btn btn-primary">Aktualisieren</button>
            </form>
        </div>
    </div>
@endsection
