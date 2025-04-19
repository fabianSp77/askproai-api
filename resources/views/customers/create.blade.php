<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Neuer Kunde | AskProAI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  @include('layouts.nav')
  <div class="container mt-4">
    <div class="d-flex justify-content-between mb-4">
      <h1>Neuen Kunden erstellen</h1>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary">Abbrechen</a>
    </div>
    <div class="card">
      <div class="card-body">
        <form action="{{ route('customers.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label">Telefon</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number">
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">E-Mail</label>
            <input type="email" class="form-control" id="email" name="email">
          </div>
          <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
