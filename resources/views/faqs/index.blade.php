@extends('layouts.public')
@section('title', 'FAQs - AskProAI')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Häufige Fragen</h1>
    <a href="{{ route('faqs.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Neue FAQ
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        @if(count($faqs) > 0)
            <div class="accordion" id="faqAccordion">
                @foreach($faqs->groupBy('category') as $category => $categoryFaqs)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#category{{ $loop->index }}">
                                {{ $category }}
                            </button>
                        </h2>
                        <div id="category{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach($categoryFaqs as $faq)
                                        <div class="list-group-item">
                                            <div class="fw-bold mb-2">{{ $faq->question }}</div>
                                            <p class="mb-0">{{ $faq->answer }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center mb-0">Noch keine FAQs vorhanden. Erstellen Sie die erste FAQ.</p>
        @endif
    </div>
</div>
@endsection
