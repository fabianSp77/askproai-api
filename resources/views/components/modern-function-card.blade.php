@props(['styles' => ''])

<div {{ $attributes->merge(['class' => 'function-card-modern']) }} 
     style="{{ $styles }}background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important; 
            backdrop-filter: blur(20px) !important; 
            -webkit-backdrop-filter: blur(20px) !important; 
            border: 1px solid rgba(99, 102, 241, 0.4) !important; 
            border-radius: 24px !important; 
            padding: 32px !important; 
            position: relative !important; 
            overflow: hidden !important; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important; 
            margin-bottom: 24px !important; 
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
            transform: translateZ(0) !important;
            will-change: transform, box-shadow !important;">
    
    {{-- Glassmorphism overlay --}}
    <div style="position: absolute !important; 
                top: 0 !important; 
                left: 0 !important; 
                right: 0 !important; 
                bottom: 0 !important; 
                background: radial-gradient(800px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(99, 102, 241, 0.1), transparent 40%) !important; 
                opacity: 0 !important; 
                transition: opacity 0.3s !important; 
                pointer-events: none !important; 
                z-index: 0 !important;"
         class="glassmorphism-overlay"></div>
    
    {{-- Content wrapper --}}
    <div style="position: relative !important; z-index: 1 !important;">
        {{ $slot }}
    </div>
    
    {{-- Inline script for this specific card --}}
    <script>
        (function() {
            const card = document.currentScript.previousElementSibling;
            if (card && card.classList.contains('function-card-modern')) {
                // Mouse tracking for gradient effect
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    card.style.setProperty('--mouse-x', `${x}%`);
                    card.style.setProperty('--mouse-y', `${y}%`);
                    card.querySelector('.glassmorphism-overlay').style.opacity = '1';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.querySelector('.glassmorphism-overlay').style.opacity = '0';
                });
                
                // Hover effects
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-8px) scale(1.02)';
                    card.style.boxShadow = '0 20px 60px -15px rgba(99, 102, 241, 0.4), 0 0 0 1px rgba(99, 102, 241, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 0 120px -20px rgba(99, 102, 241, 0.5)';
                    card.style.borderColor = 'rgba(99, 102, 241, 0.6)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateZ(0)';
                    card.style.boxShadow = '0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)';
                    card.style.borderColor = 'rgba(99, 102, 241, 0.4)';
                });
            }
        })();
    </script>
</div>