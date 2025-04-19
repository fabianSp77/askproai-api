@extends("layouts.app")

@section("content")
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kunden | AskProAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    @include('layouts.nav')
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Kundenliste</h1>
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neuer Kunde
            </a>
        </div>
        
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Telefon</th>
                            <th>E-Mail</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone_number }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>
                                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
@endsection
