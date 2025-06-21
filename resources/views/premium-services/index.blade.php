@extends('portal.layouts.app')
@section('title', 'Premium Services - AskProAI')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Premium Services</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Basis</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <h2 class="card-title pricing-card-title">€49<small class="text-muted fw-light">/mo</small></h2>
                <ul class="list-unstyled mt-3 mb-4">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Telefonische Erreichbarkeit</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Terminbuchung</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Einfache Statistiken</li>
                    <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i>Eigenes Branding</li>
                    <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i>Persönlicher Support</li>
                </ul>
                <a href="#" class="btn btn-primary mt-auto">Jetzt buchen</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Professional</h5>
                <span class="badge bg-warning text-dark position-absolute top-0 end-0 mt-2 me-2">Beliebt</span>
            </div>
            <div class="card-body d-flex flex-column">
                <h2 class="card-title pricing-card-title">€89<small class="text-muted fw-light">/mo</small></h2>
                <ul class="list-unstyled mt-3 mb-4">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Telefonische Erreichbarkeit</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Terminbuchung</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Erweiterte Statistiken</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Eigenes Branding</li>
                    <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i>Persönlicher Support</li>
                </ul>
                <a href="#" class="btn btn-primary mt-auto">Jetzt buchen</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Enterprise</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <h2 class="card-title pricing-card-title">€149<small class="text-muted fw-light">/mo</small></h2>
                <ul class="list-unstyled mt-3 mb-4">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Telefonische Erreichbarkeit</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Terminbuchung</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Erweiterte Statistiken</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Eigenes Branding</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Persönlicher Support</li>
                </ul>
                <a href="#" class="btn btn-primary mt-auto">Jetzt buchen</a>
            </div>
        </div>
    </div>
</div>
@endsection
