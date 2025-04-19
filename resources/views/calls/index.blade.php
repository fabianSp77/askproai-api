@extends("layouts.app")

@section("content")
<!DOCTYPE html>
<html lang="de">
<head>
   <title>AskProAI - Anrufliste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
@include("layouts.nav")
   <div class="container mt-4">
       <h2>Anrufliste</h2>
       <table class="table">
           <thead><tr><th>Datum</th><th>ID</th><th>Status</th><th>Sentiment</th><th>Aktionen</th></tr></thead>
           <tbody>
               @foreach($calls as $call)
               <tr>
                   <td>{{ $call->call_time }}</td>
                   <td>{{ substr($call->call_id, 0, 20) }}...</td>
                   <td>
                       @if($call->successful)
                           <span class="badge bg-success">Erfolgreich</span>
                       @else
                           <span class="badge bg-danger">Nicht erfolgreich</span>
                       @endif
                   </td>
                   <td>{{ $call->user_sentiment }}</td>
                   <td><a href="{{ route('calls.show', $call->id) }}" class="btn btn-sm btn-info">Details</a></td>
               </tr>
               @endforeach
           </tbody>
       </table>
   </div>
</body>
</html>
@endsection
