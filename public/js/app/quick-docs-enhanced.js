// Quick Docs Enhanced - Interactive Documentation Hub

import Fuse from 'fuse.js';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

class QuickDocsEnhanced {
    constructor() {
        this.searchIndex = null;
        this.favoritesDocs = new Set();
        this.readingProgress = new Map();
        this.viewMode = 'grid';
        this.currentFilter = {
            categories: [],
            difficulty: '',
            tags: []
        };
        
        this.init();
    }
    
    async init() {
        await this.initializeSearch();
        this.bindEventListeners();
        this.initializeAnimations();
        this.loadUserPreferences();
        this.initializeKeyboardShortcuts();
        this.initializeTouchGestures();
        this.setupIntersectionObserver();
        this.initializeAnalytics();
    }
    
    async initializeSearch() {
        // Fetch all documents for indexing
        const documents = await this.fetchDocuments();
        
        // Configure Fuse.js for fuzzy search
        const options = {
            keys: [
                { name: 'title', weight: 0.3 },
                { name: 'description', weight: 0.2 },
                { name: 'content', weight: 0.2 },
                { name: 'tags', weight: 0.15 },
                { name: 'aiSummary', weight: 0.15 }
            ],
            threshold: 0.3,
            includeScore: true,
            includeMatches: true,
            minMatchCharLength: 2,
            useExtendedSearch: true
        };
        
        this.searchIndex = new Fuse(documents, options);
    }
    
    bindEventListeners() {
        // Search input with debouncing
        const searchInput = document.querySelector('#doc-search');
        let searchTimeout;
        
        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });
        
        // Category filters
        document.querySelectorAll('.category-filter').forEach(filter => {
            filter.addEventListener('click', () => {
                this.toggleCategoryFilter(filter.dataset.category);
            });
        });
        
        // View mode toggle
        document.querySelectorAll('.view-mode-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                this.setViewMode(toggle.dataset.mode);
            });
        });
        
        // Favorite toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('.favorite-toggle')) {
                const docId = e.target.closest('.favorite-toggle').dataset.docId;
                this.toggleFavorite(docId);
            }
        });
        
        // Export functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.export-doc')) {
                const docId = e.target.closest('.export-doc').dataset.docId;
                this.exportDocument(docId);
            }
        });
        
        // Share functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.share-doc')) {
                const docId = e.target.closest('.share-doc').dataset.docId;
                this.shareDocument(docId);
            }
        });
    }
    
    initializeAnimations() {
        // Card entrance animations
        gsap.from('.doc-card', {
            opacity: 0,
            y: 30,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.docs-grid',
                start: 'top 80%'
            }
        });
        
        // Progress bar animations
        document.querySelectorAll('.progress-bar-fill').forEach(bar => {
            const progress = bar.dataset.progress || 0;
            gsap.to(bar, {
                width: `${progress}%`,
                duration: 1,
                ease: 'power2.inOut',
                scrollTrigger: {
                    trigger: bar,
                    start: 'top 90%'
                }
            });
        });
        
        // Hover effects with GSAP
        document.querySelectorAll('.doc-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    scale: 1.02,
                    boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)',
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    scale: 1,
                    boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });
    }
    
    loadUserPreferences() {
        // Load from localStorage
        const preferences = JSON.parse(localStorage.getItem('docPreferences') || '{}');
        
        this.viewMode = preferences.viewMode || 'grid';
        this.favoritesDocs = new Set(preferences.favorites || []);
        this.readingProgress = new Map(Object.entries(preferences.readingProgress || {}));
        
        // Apply preferences to UI
        this.applyUserPreferences();
    }
    
    saveUserPreferences() {
        const preferences = {
            viewMode: this.viewMode,
            favorites: Array.from(this.favoritesDocs),
            readingProgress: Object.fromEntries(this.readingProgress)
        };
        
        localStorage.setItem('docPreferences', JSON.stringify(preferences));
    }
    
    initializeKeyboardShortcuts() {
        const shortcuts = {
            '/': () => this.focusSearch(),
            'cmd+k': () => this.openCommandPalette(),
            'ctrl+k': () => this.openCommandPalette(),
            'g h': () => this.navigateTo('/admin'),
            'g d': () => this.navigateTo('/admin/docs-enhanced'),
            'f': () => this.toggleFavoritesFilter(),
            'r': () => this.showRecentlyViewed(),
            '?': () => this.showHelp(),
            'escape': () => this.closeModals()
        };
        
        // Hotkeys library integration
        let lastKeyTime = 0;
        let keyBuffer = '';
        
        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            const key = this.getKeyString(e);
            
            // Reset buffer if too much time passed
            if (now - lastKeyTime > 500) {
                keyBuffer = '';
            }
            
            keyBuffer += key;
            lastKeyTime = now;
            
            // Check shortcuts
            Object.entries(shortcuts).forEach(([shortcut, handler]) => {
                if (keyBuffer.endsWith(shortcut)) {
                    e.preventDefault();
                    handler();
                    keyBuffer = '';
                }
            });
        });
    }
    
    getKeyString(e) {
        let key = '';
        if (e.metaKey) key += 'cmd+';
        if (e.ctrlKey) key += 'ctrl+';
        if (e.altKey) key += 'alt+';
        if (e.shiftKey) key += 'shift+';
        key += e.key.toLowerCase();
        return key;
    }
    
    initializeTouchGestures() {
        // Swipe gestures for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        });
        
        this.handleSwipe = () => {
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) < 50) return;
            
            if (swipeDistance > 0) {
                // Swipe right - show favorites
                this.toggleFavoritesFilter();
            } else {
                // Swipe left - show all
                this.clearFilters();
            }
        };
    }
    
    setupIntersectionObserver() {
        // Track reading progress
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const docId = entry.target.dataset.docId;
                    const section = entry.target.dataset.section;
                    this.updateReadingProgress(docId, section);
                }
            });
        }, {
            threshold: 0.5
        });
        
        // Observe document sections
        document.querySelectorAll('.doc-section').forEach(section => {
            observer.observe(section);
        });
    }
    
    async performSearch(query) {
        if (!query || query.length < 2) {
            this.displayAllDocuments();
            return;
        }
        
        const results = this.searchIndex.search(query);
        this.displaySearchResults(results);
        
        // AI-powered query understanding
        if (query.includes('?')) {
            const answer = await this.getAIAnswer(query);
            this.displayAIAnswer(answer);
        }
    }
    
    async getAIAnswer(question) {
        // This would integrate with your AI service
        try {
            const response = await fetch('/api/docs/ai-answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ question })
            });
            
            return await response.json();
        } catch (error) {
            console.error('AI answer failed:', error);
            return null;
        }
    }
    
    toggleFavorite(docId) {
        if (this.favoritesDocs.has(docId)) {
            this.favoritesDocs.delete(docId);
            this.animateRemoveFavorite(docId);
        } else {
            this.favoritesDocs.add(docId);
            this.animateAddFavorite(docId);
        }
        
        this.saveUserPreferences();
        this.updateFavoriteUI(docId);
    }
    
    animateAddFavorite(docId) {
        const element = document.querySelector(`[data-doc-id="${docId}"] .favorite-icon`);
        
        gsap.timeline()
            .to(element, {
                scale: 1.5,
                rotation: 360,
                duration: 0.3,
                ease: 'power2.out'
            })
            .to(element, {
                scale: 1,
                duration: 0.2,
                ease: 'power2.in'
            });
        
        // Particle effect
        this.createParticles(element, 'gold');
    }
    
    createParticles(element, color) {
        const particles = 8;
        const rect = element.getBoundingClientRect();
        
        for (let i = 0; i < particles; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.position = 'fixed';
            particle.style.left = `${rect.left + rect.width / 2}px`;
            particle.style.top = `${rect.top + rect.height / 2}px`;
            particle.style.width = '4px';
            particle.style.height = '4px';
            particle.style.backgroundColor = color;
            particle.style.borderRadius = '50%';
            particle.style.pointerEvents = 'none';
            
            document.body.appendChild(particle);
            
            const angle = (i / particles) * Math.PI * 2;
            const distance = 50 + Math.random() * 50;
            
            gsap.to(particle, {
                x: Math.cos(angle) * distance,
                y: Math.sin(angle) * distance,
                opacity: 0,
                duration: 0.8,
                ease: 'power2.out',
                onComplete: () => particle.remove()
            });
        }
    }
    
    async exportDocument(docId) {
        // Show loading state
        this.showLoading('Generating PDF...');
        
        try {
            const response = await fetch(`/api/docs/${docId}/export`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `document-${docId}.pdf`;
                a.click();
                window.URL.revokeObjectURL(url);
                
                this.showSuccess('PDF exported successfully!');
            }
        } catch (error) {
            this.showError('Export failed. Please try again.');
        } finally {
            this.hideLoading();
        }
    }
    
    async shareDocument(docId) {
        const doc = this.getDocumentById(docId);
        const shareUrl = `${window.location.origin}/docs/${docId}`;
        
        if (navigator.share) {
            // Native share API
            try {
                await navigator.share({
                    title: doc.title,
                    text: doc.description,
                    url: shareUrl
                });
            } catch (error) {
                // User cancelled or error
                console.log('Share cancelled');
            }
        } else {
            // Fallback to clipboard
            await navigator.clipboard.writeText(shareUrl);
            this.showSuccess('Link copied to clipboard!');
        }
    }
    
    initializeAnalytics() {
        // Track page views
        this.trackEvent('page_view', {
            page: 'documentation_hub'
        });
        
        // Track search queries
        document.querySelector('#doc-search')?.addEventListener('blur', (e) => {
            if (e.target.value) {
                this.trackEvent('search', {
                    query: e.target.value
                });
            }
        });
        
        // Track document views
        document.addEventListener('click', (e) => {
            const docLink = e.target.closest('a[data-doc-id]');
            if (docLink) {
                this.trackEvent('doc_view', {
                    doc_id: docLink.dataset.docId,
                    doc_title: docLink.dataset.docTitle
                });
            }
        });
    }
    
    trackEvent(eventName, data) {
        // Google Analytics 4
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, data);
        }
        
        // Custom analytics endpoint
        fetch('/api/analytics/track', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
            body: JSON.stringify({
                event: eventName,
                properties: data,
                timestamp: new Date().toISOString()
            })
        }).catch(() => {
            // Silent fail for analytics
        });
    }
    
    // UI Helper Methods
    showLoading(message) {
        const loader = document.createElement('div');
        loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loader.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center gap-4">
                <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
        loader.id = 'global-loader';
        document.body.appendChild(loader);
    }
    
    hideLoading() {
        document.getElementById('global-loader')?.remove();
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 flex items-center gap-3 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        } text-white transform translate-x-full`;
        
        notification.innerHTML = `
            ${type === 'success' ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' : 
              type === 'error' ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>' :
              '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'}
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.transition = 'transform 0.3s ease-out';
        });
        
        // Auto remove
        setTimeout(() => {
            notification.style.transform = 'translateX(120%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.quickDocsEnhanced = new QuickDocsEnhanced();
    });
} else {
    window.quickDocsEnhanced = new QuickDocsEnhanced();
}

export default QuickDocsEnhanced;