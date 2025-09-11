@extends('layouts.admin')
@section('title', 'Premium-Services')
@section('content')
    <h1>Premium-Services</h1>
    <a href="{{ route('admin.premium-services.create') }}" class="btn btn-primary mb-3">Neuen Service erstellen</a>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Preis</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ number_format($service->price, 2, ',', '.') }} €</td>
                            <td>{{ $service->active ? 'Aktiv' : 'Inaktiv' }}</td>
                            <td>
                                <a href="{{ route('admin.premium-services.edit', $service->id) }}" class="btn btn-sm btn-primary">Bearbeiten</a>
                                <form action="{{ route('admin.premium-services.destroy', $service->id) }}" method="POST" style="display: inline-block;">
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
    </div>
@endsection
