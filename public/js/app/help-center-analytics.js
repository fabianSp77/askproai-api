/**
 * Help Center Analytics JavaScript
 * Handles feedback submission and search click tracking
 */

// Track search result clicks
document.addEventListener('DOMContentLoaded', function() {
    // Search result click tracking
    const searchResults = document.querySelectorAll('.search-result-link');
    searchResults.forEach(link => {
        link.addEventListener('click', function(e) {
            const url = this.href;
            const query = this.dataset.query;
            
            // Send tracking request
            fetch('/hilfe/api/track-search-click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    query: query,
                    clicked_url: url
                })
            });
        });
    });

    // Article feedback functionality
    const feedbackSection = document.getElementById('article-feedback');
    if (feedbackSection) {
        const helpfulBtn = document.getElementById('helpful-yes');
        const notHelpfulBtn = document.getElementById('helpful-no');
        const feedbackForm = document.getElementById('feedback-form');
        const feedbackThanks = document.getElementById('feedback-thanks');
        const feedbackError = document.getElementById('feedback-error');
        const commentSection = document.getElementById('comment-section');
        const submitBtn = document.getElementById('submit-feedback');
        
        let feedbackData = {
            category: feedbackSection.dataset.category,
            topic: feedbackSection.dataset.topic,
            helpful: null,
            comment: null
        };

        // Handle helpful/not helpful buttons
        helpfulBtn?.addEventListener('click', function() {
            feedbackData.helpful = true;
            showCommentSection();
            highlightButton(this);
        });

        notHelpfulBtn?.addEventListener('click', function() {
            feedbackData.helpful = false;
            showCommentSection();
            highlightButton(this);
        });

        // Handle feedback submission
        submitBtn?.addEventListener('click', function() {
            const commentInput = document.getElementById('feedback-comment');
            feedbackData.comment = commentInput.value;

            // Disable form while submitting
            this.disabled = true;
            helpfulBtn.disabled = true;
            notHelpfulBtn.disabled = true;

            // Submit feedback
            fetch('/hilfe/api/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(feedbackData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedbackForm.style.display = 'none';
                    feedbackThanks.style.display = 'block';
                    feedbackThanks.textContent = data.message;
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showError('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
            });
        });

        function showCommentSection() {
            commentSection.style.display = 'block';
            submitBtn.style.display = 'inline-flex';
        }

        function highlightButton(button) {
            // Remove highlight from both buttons
            helpfulBtn.classList.remove('bg-green-100', 'text-green-800', 'border-green-300');
            notHelpfulBtn.classList.remove('bg-red-100', 'text-red-800', 'border-red-300');
            
            // Add highlight to clicked button
            if (button === helpfulBtn) {
                button.classList.add('bg-green-100', 'text-green-800', 'border-green-300');
            } else {
                button.classList.add('bg-red-100', 'text-red-800', 'border-red-300');
            }
        }

        function showError(message) {
            feedbackError.style.display = 'block';
            feedbackError.textContent = message;
            
            // Re-enable form
            submitBtn.disabled = false;
            helpfulBtn.disabled = false;
            notHelpfulBtn.disabled = false;
        }
    }
});

// Export for use in other scripts if needed
window.HelpCenterAnalytics = {
    trackPageView: function(category, topic) {
        // Page views are tracked server-side
    },
    
    trackSearch: function(query, resultsCount) {
        // Search queries are tracked server-side
    }
};