@extends('layouts.admin')

@section('title', 'FAQ Verwaltung')

@section('content')
    <h1>FAQ Verwaltung</h1>
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary mb-3">Neue FAQ erstellen</a>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Frage</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($faqs as $faq)
                    <tr>
                        <td>{{ $faq->question }}</td>
                        <td>{{ $faq->active ? 'Aktiv' : 'Inaktiv' }}</td>
                        <td>
                            <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-sm btn-primary">Bearbeiten</a>
                            <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
