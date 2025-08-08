/**
 * Coffee Break Mode - Stress Relief for Call Center Agents
 * A delightful mini-break feature for high-stress moments
 */

class CoffeeBreakMode {
    constructor() {
        this.isActive = false;
        this.breakDuration = 30000; // 30 seconds default
        this.encouragingMessages = [
            "☕ Kaffeepause! Du hast dir eine kurze Erholung verdient!",
            "🌸 Tief durchatmen... Du machst das großartig!",
            "🧘 Entspannung pur - gleich geht es erfrischt weiter!",
            "🌈 Kurze Pause für die Seele - du bist wichtig!",
            "💆 Stress lass nach - du schaffst alles mit Ruhe!",
            "🦋 Leichtigkeit kehrt zurück - du bist stark!",
            "🌺 Moment der Ruhe - deutsche Gründlichkeit braucht auch Pausen!"
        ];
        
        this.relaxingColors = [
            'rgba(156, 163, 175, 0.1)', // warm gray
            'rgba(34, 197, 94, 0.08)',  // soft green  
            'rgba(59, 130, 246, 0.06)', // calm blue
            'rgba(168, 85, 247, 0.05)', // gentle purple
        ];
        
        this.init();
    }
    
    init() {
        // Add coffee break button to interface
        this.createCoffeeBreakButton();
        
        // Listen for stress indicators
        this.detectStressSignals();
        
        // Keyboard shortcut: Ctrl + Alt + C
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.altKey && e.key === 'c') {
                e.preventDefault();
                this.activateCoffeeBreak();
            }
        });
    }
    
    createCoffeeBreakButton() {
        const button = document.createElement('button');
        button.innerHTML = '☕';
        button.className = `
            fixed bottom-20 right-4 w-14 h-14 
            bg-gradient-to-br from-amber-400 to-orange-500 
            text-white text-2xl rounded-full shadow-lg 
            hover:shadow-xl transition-all duration-300 
            z-50 coffee-break-btn
        `;
        button.title = 'Kaffeepause - 30 Sekunden Entspannung (Ctrl+Alt+C)';
        
        button.addEventListener('click', () => this.activateCoffeeBreak());
        
        // Gentle pulsing animation
        setInterval(() => {
            if (!this.isActive && Math.random() < 0.3) {
                button.style.animation = 'gentle-pulse 2s ease-in-out';
                setTimeout(() => button.style.animation = '', 2000);
            }
        }, 10000);
        
        document.body.appendChild(button);
    }
    
    detectStressSignals() {
        let rapidClicks = 0;
        let lastClickTime = 0;
        
        // Detect rapid clicking (stress indicator)
        document.addEventListener('click', () => {
            const now = Date.now();
            if (now - lastClickTime < 1000) {
                rapidClicks++;
                if (rapidClicks >= 5) {
                    // Suggest coffee break after 5 rapid clicks
                    this.suggestCoffeeBreak();
                    rapidClicks = 0;
                }
            } else {
                rapidClicks = 0;
            }
            lastClickTime = now;
        });
        
        // Suggest break every hour
        setInterval(() => {
            if (this.shouldSuggestBreak()) {
                this.suggestCoffeeBreak();
            }
        }, 3600000); // 1 hour
    }
    
    shouldSuggestBreak() {
        const lastBreak = localStorage.getItem('lastCoffeeBreak');
        if (!lastBreak) return true;
        
        const timeSinceBreak = Date.now() - parseInt(lastBreak);
        return timeSinceBreak > 3600000; // 1 hour
    }
    
    suggestCoffeeBreak() {
        if (this.isActive) return;
        
        const suggestion = document.createElement('div');
        suggestion.className = `
            fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2
            bg-gradient-to-r from-amber-100 to-orange-100 
            border-2 border-amber-300 rounded-2xl p-6 
            shadow-2xl z-[9999] coffee-suggestion
        `;
        
        suggestion.innerHTML = `
            <div class="text-center">
                <div class="text-4xl mb-3">☕</div>
                <div class="text-lg font-semibold text-amber-800 mb-2">
                    Stress-Level erkannt!
                </div>
                <div class="text-sm text-amber-700 mb-4">
                    Wie wäre es mit einer entspannenden 30-Sekunden-Pause?
                </div>
                <div class="flex gap-3 justify-center">
                    <button class="coffee-yes-btn px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors">
                        ☕ Ja, gerne!
                    </button>
                    <button class="coffee-no-btn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Später
                    </button>
                </div>
            </div>
        `;
        
        suggestion.querySelector('.coffee-yes-btn').addEventListener('click', () => {
            suggestion.remove();
            this.activateCoffeeBreak();
        });
        
        suggestion.querySelector('.coffee-no-btn').addEventListener('click', () => {
            suggestion.remove();
        });
        
        document.body.appendChild(suggestion);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (suggestion.parentNode) {
                suggestion.remove();
            }
        }, 10000);
    }
    
    activateCoffeeBreak() {
        if (this.isActive) return;
        
        this.isActive = true;
        localStorage.setItem('lastCoffeeBreak', Date.now().toString());
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'coffee-break-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, ${this.relaxingColors.join(', ')});
            backdrop-filter: blur(5px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 1s ease-out;
        `;
        
        // Create content
        const content = document.createElement('div');
        content.className = 'coffee-content text-center';
        content.style.cssText = `
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            animation: slideIn 1s ease-out;
        `;
        
        const message = this.encouragingMessages[Math.floor(Math.random() * this.encouragingMessages.length)];
        
        content.innerHTML = `
            <div class="text-6xl mb-4 animate-bounce">☕</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Kaffeepause</h2>
            <p class="text-lg text-gray-600 mb-6">${message}</p>
            <div class="coffee-timer text-4xl font-bold text-amber-600 mb-4">30</div>
            <div class="text-sm text-gray-500">Sekunden bis zur Rückkehr...</div>
            <div class="mt-6">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="coffee-progress bg-gradient-to-r from-amber-400 to-orange-500 h-3 rounded-full transition-all duration-1000 ease-linear" style="width: 100%"></div>
                </div>
            </div>
        `;
        
        overlay.appendChild(content);
        document.body.appendChild(overlay);
        
        // Start countdown
        this.startCountdown(content);
        
        // Play relaxing sound if enabled
        if (window.ModernCallInteractions && new window.ModernCallInteractions().soundEnabled) {
            this.playRelaxingSound();
        }
    }
    
    startCountdown(content) {
        const timer = content.querySelector('.coffee-timer');
        const progress = content.querySelector('.coffee-progress');
        let timeLeft = 30;
        
        const interval = setInterval(() => {
            timeLeft--;
            timer.textContent = timeLeft;
            
            const progressPercent = (timeLeft / 30) * 100;
            progress.style.width = progressPercent + '%';
            
            if (timeLeft <= 0) {
                clearInterval(interval);
                this.endCoffeeBreak();
            }
        }, 1000);
    }
    
    endCoffeeBreak() {
        const overlay = document.querySelector('.coffee-break-overlay');
        if (overlay) {
            overlay.style.animation = 'fadeOut 1s ease-out';
            setTimeout(() => {
                overlay.remove();
            }, 1000);
        }
        
        this.isActive = false;
        
        // Show energizing message
        this.showWelcomeBackMessage();
    }
    
    showWelcomeBackMessage() {
        const messages = [
            "🌟 Willkommen zurück! Erfrischt und bereit für neue Herausforderungen!",
            "💪 Pause beendet! Du bist jetzt noch stärker!",
            "✨ Erfrischt und motiviert - jetzt wird's wieder richtig gut!",
            "🚀 Energy-Level aufgeladen! Bereit für Service-Excellence!",
            "🎯 Zurück im Spiel! Deine Kunden warten auf dich!"
        ];
        
        const message = messages[Math.floor(Math.random() * messages.length)];
        
        // Show welcome back notification
        if (typeof window.ModernCallInteractions !== 'undefined') {
            const interactions = new window.ModernCallInteractions();
            interactions.showPersonalityToast(message, 'success');
        }
    }
    
    playRelaxingSound() {
        // Gentle, calming chord progression
        const relaxingChords = [
            [261, 329, 392], // C major
            [294, 370, 440], // D minor  
            [329, 415, 494], // E minor
            [261, 329, 392], // C major
        ];
        
        relaxingChords.forEach((chord, index) => {
            setTimeout(() => {
                this.playChord(chord, 2000, 0.05);
            }, index * 2000);
        });
    }
    
    playChord(frequencies, duration, volume = 0.1) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
            frequencies.forEach(freq => {
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                
                oscillator.frequency.setValueAtTime(freq, audioCtx.currentTime);
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
                gainNode.gain.linearRampToValueAtTime(volume / frequencies.length, audioCtx.currentTime + 0.5);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration / 1000);
                
                oscillator.start(audioCtx.currentTime);
                oscillator.stop(audioCtx.currentTime + duration / 1000);
            });
        } catch (error) {
            console.warn('Relaxing audio failed:', error);
        }
    }
}

// CSS for coffee break animations
const coffeeStyle = document.createElement('style');
coffeeStyle.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes slideIn {
        from { 
            opacity: 0; 
            transform: translateY(50px) scale(0.9); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }
    
    @keyframes gentle-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .coffee-break-btn:hover {
        transform: scale(1.1) rotate(5deg);
    }
    
    .coffee-suggestion {
        animation: gentle-bounce 0.5s ease-out;
    }
    
    @keyframes gentle-bounce {
        0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
        50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.8; }
        100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    }
`;

document.head.appendChild(coffeeStyle);

// Auto-initialize on call management pages
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (window.location.pathname.includes('call') || window.location.pathname.includes('admin')) {
            new CoffeeBreakMode();
        }
    });
} else {
    if (window.location.pathname.includes('call') || window.location.pathname.includes('admin')) {
        new CoffeeBreakMode();
    }
}

export default CoffeeBreakMode;