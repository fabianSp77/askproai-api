@extends('portal.layouts.app')

@section('title', 'Anrufdetails - AskProAI')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>Anrufdetails</h1>
  <a href="{{ route('calls.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i>Zurück
  </a>
</div>

<div class="card">
  <div class="card-header">
    <strong>{{ $call->call_id }}</strong>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <p><strong>Datum:</strong> {{ $call->call_time }}</p>
        <p><strong>Dauer:</strong> {{ $call->call_duration }}</p>
        <p><strong>Status:</strong> 
          @if($call->successful)
            <span class="badge bg-success">Erfolgreich</span>
          @else
            <span class="badge bg-danger">Nicht erfolgreich</span>
          @endif
        </p>
      </div>
      <div class="col-md-6">
        <p><strong>Sentiment:</strong> {{ $call->user_sentiment }}</p>
        <p><strong>Kosten:</strong> ${{ $call->cost }}</p>
      </div>
    </div>
  </div>
</div>
@endsection
