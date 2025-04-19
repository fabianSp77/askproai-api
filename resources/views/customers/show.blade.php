<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Kundendetails | AskProAI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  @include('layouts.nav')
  <div class="container mt-4">
    <div class="d-flex justify-content-between mb-4">
      <h1>{{ $customer->name }}</h1>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary">Zurück</a>
    </div>
    <div class="row">
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header">Kontaktdaten</div>
          <div class="card-body">
            <p><strong>Telefon:</strong> {{ $customer->phone_number }}</p>
            <p><strong>E-Mail:</strong> {{ $customer->email }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">Anrufe</div>
          <div class="card-body p-0">
            <table class="table mb-0">
              <thead class="table-light">
                <tr>
                  <th>Datum</th>
                  <th>Dauer</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($calls as $call)
                <tr>
                  <td>{{ $call->call_time }}</td>
                  <td>{{ $call->call_duration }}</td>
                  <td>{{ $call->successful ? 'Erfolgreich' : 'Nicht erfolgreich' }}</td>
                  <td>
                    <a href="{{ route('calls.show', $call->id) }}" class="btn btn-sm btn-info">
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
    </div>
  </div>
</body>
</html>
