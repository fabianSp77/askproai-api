@extends('portal.layouts.app')

@section('title', $title . ' - Hilfe-Center')

@section('styles')
<style>
    .article-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .article-sidebar {
        position: sticky;
        top: 2rem;
        max-height: calc(100vh - 4rem);
        overflow-y: auto;
    }
    
    .article-content {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 2rem;
        min-height: 60vh;
    }
    
    .article-content h1, 
    .article-content h2, 
    .article-content h3 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #111827;
    }
    
    .article-content h1 {
        font-size: 2rem;
        font-weight: 700;
        border-bottom: 2px solid #E5E7EB;
        padding-bottom: 0.5rem;
    }
    
    .article-content h2 {
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .article-content h3 {
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .article-content p {
        margin-bottom: 1rem;
        line-height: 1.75;
        color: #374151;
    }
    
    .article-content ul, 
    .article-content ol {
        margin-bottom: 1rem;
        padding-left: 2rem;
    }
    
    .article-content li {
        margin-bottom: 0.5rem;
        line-height: 1.75;
    }
    
    .article-content code {
        background: #F3F4F6;
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.875rem;
    }
    
    .article-content pre {
        background: #1F2937;
        color: #F9FAFB;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin-bottom: 1rem;
    }
    
    .article-content pre code {
        background: transparent;
        padding: 0;
        color: inherit;
    }
    
    .article-content blockquote {
        border-left: 4px solid #3B82F6;
        padding-left: 1rem;
        margin: 1rem 0;
        color: #6B7280;
        font-style: italic;
    }
    
    .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 0;
        color: #6B7280;
        font-size: 0.875rem;
    }
    
    .breadcrumbs a {
        color: #3B82F6;
        text-decoration: none;
    }
    
    .breadcrumbs a:hover {
        text-decoration: underline;
    }
    
    .breadcrumbs .separator {
        color: #9CA3AF;
    }
    
    .category-nav {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 1.5rem;
    }
    
    .category-nav-title {
        font-weight: 600;
        margin-bottom: 1rem;
        color: #111827;
    }
    
    .category-nav-item {
        display: block;
        padding: 0.5rem 1rem;
        color: #374151;
        text-decoration: none;
        border-radius: 0.375rem;
        margin-bottom: 0.25rem;
        transition: all 0.2s;
    }
    
    .category-nav-item:hover {
        background: #F3F4F6;
        color: #3B82F6;
    }
    
    .category-nav-item.active {
        background: #EBF5FF;
        color: #3B82F6;
        font-weight: 500;
    }
    
    .article-meta {
        display: flex;
        align-items: center;
        gap: 2rem;
        padding: 1rem 0;
        border-bottom: 1px solid #E5E7EB;
        margin-bottom: 2rem;
        color: #6B7280;
        font-size: 0.875rem;
    }
    
    .article-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .feedback-section {
        background: #F9FAFB;
        border-radius: 0.75rem;
        padding: 2rem;
        margin-top: 3rem;
        text-align: center;
    }
    
    .feedback-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1rem;
    }
    
    .feedback-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: 2px solid #E5E7EB;
        border-radius: 0.5rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .feedback-button:hover {
        border-color: #3B82F6;
        transform: translateY(-2px);
    }
    
    .feedback-button.selected-yes {
        background: #10B981;
        color: white;
        border-color: #10B981;
    }
    
    .feedback-button.selected-no {
        background: #EF4444;
        color: white;
        border-color: #EF4444;
    }
    
    .related-articles {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px solid #E5E7EB;
    }
    
    .related-article-card {
        display: block;
        padding: 1rem;
        border: 1px solid #E5E7EB;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .related-article-card:hover {
        border-color: #3B82F6;
        background: #F9FAFB;
        transform: translateX(4px);
    }
    
    .back-to-top {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #3B82F6;
        color: white;
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s;
        z-index: 40;
    }
    
    .back-to-top.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .back-to-top:hover {
        background: #2563EB;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .article-sidebar {
            display: none;
        }
        
        .article-content {
            padding: 1.5rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
        @foreach($breadcrumbs as $index => $crumb)
            @if($crumb['url'])
                <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
            @else
                <span class="text-gray-800">{{ $crumb['name'] }}</span>
            @endif
            
            @if($index < count($breadcrumbs) - 1)
                <span class="separator">›</span>
            @endif
        @endforeach
    </nav>
    
    <div class="article-container">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Navigation -->
            <aside class="lg:col-span-1">
                <div class="article-sidebar">
                    <div class="category-nav">
                        <h3 class="category-nav-title">{{ $breadcrumbs[1]['name'] }}</h3>
                        <nav>
                            @foreach($categoryArticles as $article)
                                <a href="{{ $article['url'] }}" 
                                   class="category-nav-item @if($article['topic'] === $topic) active @endif">
                                    {{ $article['title'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                    
                    <!-- Quick Help -->
                    <div class="mt-6 bg-blue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">Brauchen Sie Hilfe?</h4>
                        <p class="text-sm text-blue-700 mb-3">Unser Support-Team ist für Sie da!</p>
                        <a href="mailto:support@askproai.de" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            E-Mail schreiben
                        </a>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="lg:col-span-3">
                <article class="article-content">
                    <!-- Article Meta -->
                    <div class="article-meta">
                        <div class="article-meta-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>{{ number_format($viewCount) }} Aufrufe</span>
                        </div>
                        @if($feedbackStats['total'] > 0)
                        <div class="article-meta-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                            </svg>
                            <span>{{ round($feedbackStats['helpful_percentage']) }}% fanden dies hilfreich</span>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Article Content -->
                    <div class="prose max-w-none">
                        {!! $content !!}
                    </div>
                    
                    <!-- Feedback Section -->
                    <div class="feedback-section" id="feedback-section">
                        <h3 class="text-lg font-semibold mb-2">War dieser Artikel hilfreich?</h3>
                        <div class="feedback-buttons">
                            <button class="feedback-button" data-helpful="1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                </svg>
                                Ja, hilfreich
                            </button>
                            <button class="feedback-button" data-helpful="0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path>
                                </svg>
                                Nicht hilfreich
                            </button>
                        </div>
                        <div id="feedback-comment" class="hidden mt-4">
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                      rows="3" 
                                      placeholder="Möchten Sie uns mitteilen, was wir verbessern können?"
                                      id="feedback-comment-text"></textarea>
                            <button class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" id="submit-feedback">
                                Feedback senden
                            </button>
                        </div>
                        <div id="feedback-thanks" class="hidden mt-4 text-green-600">
                            <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Vielen Dank für Ihr Feedback!
                        </div>
                    </div>
                    
                    <!-- Related Articles -->
                    @if(count($relatedArticles) > 0)
                    <div class="related-articles">
                        <h3 class="text-xl font-semibold mb-4">Ähnliche Artikel</h3>
                        @foreach($relatedArticles as $related)
                        <a href="{{ $related['url'] }}" class="related-article-card">
                            <h4 class="font-semibold text-gray-800 mb-1">{{ $related['title'] }}</h4>
                            <p class="text-sm text-gray-600">Mehr erfahren →</p>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </article>
            </main>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<button class="back-to-top" id="back-to-top">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Feedback handling
    const feedbackButtons = document.querySelectorAll('.feedback-button');
    const feedbackComment = document.getElementById('feedback-comment');
    const feedbackThanks = document.getElementById('feedback-thanks');
    const submitFeedback = document.getElementById('submit-feedback');
    let selectedHelpful = null;
    
    feedbackButtons.forEach(button => {
        button.addEventListener('click', function() {
            selectedHelpful = this.dataset.helpful === '1';
            
            // Update button states
            feedbackButtons.forEach(btn => {
                btn.classList.remove('selected-yes', 'selected-no');
            });
            
            if (selectedHelpful) {
                this.classList.add('selected-yes');
                // Submit immediately for positive feedback
                submitFeedbackData(true, '');
            } else {
                this.classList.add('selected-no');
                feedbackComment.classList.remove('hidden');
            }
        });
    });
    
    submitFeedback.addEventListener('click', function() {
        const comment = document.getElementById('feedback-comment-text').value;
        submitFeedbackData(false, comment);
    });
    
    function submitFeedbackData(helpful, comment) {
        fetch('{{ route('help.feedback') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                category: '{{ $category }}',
                topic: '{{ $topic }}',
                helpful: helpful,
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                feedbackComment.classList.add('hidden');
                feedbackThanks.classList.remove('hidden');
                feedbackButtons.forEach(btn => btn.disabled = true);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Back to top button
    const backToTop = document.getElementById('back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Add anchor links to headers
    document.querySelectorAll('.article-content h2, .article-content h3').forEach(header => {
        const id = header.textContent.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
        header.id = id;
        
        const anchor = document.createElement('a');
        anchor.href = '#' + id;
        anchor.className = 'header-anchor';
        anchor.innerHTML = '#';
        anchor.style.marginLeft = '0.5rem';
        anchor.style.opacity = '0.3';
        anchor.style.textDecoration = 'none';
        
        header.appendChild(anchor);
    });
});
</script>
@endsection