/**
 * Modern Call Interactions - Subtile, professionelle Delights für Call Resource
 * Enthält Micro-Animations, Easter Eggs und playful Feedback
 */

class ModernCallInteractions {
    constructor() {
        this.soundEnabled = this.getSoundPreference();
        this.konamiSequence = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'KeyB', 'KeyA'];
        this.konamiIndex = 0;
        this.celebrationMessages = [
            "Super! 🎉 Termin erfolgreich gebucht - Du bist der Hammer!",
            "Fantastisch! ✨ Wieder ein glücklicher Kunde - Spitzenleistung!",
            "Exzellent! 🚀 Das Call Center brummt dank dir!",
            "Perfekt! 💫 Deutscher Service vom Allerfeinsten!",
            "Wunderbar! 🎯 Mission erfüllt - Champion!",
            "Brilliant! 🏆 Aus Anruf wird Geschäft - Profi-Level!",
            "Traumhaft! ⭐ Kundenzufriedenheit garantiert!",
            "Phänomenal! 💪 Du machst den Unterschied!"
        ];
        
        this.motivationalQuotes = [
            "Jeder Anruf ist eine Chance, jemandes Tag zu verbessern! 🌟",
            "Dein Lächeln hört man auch am Telefon! 😊",
            "Service-Excellence beginnt mit deiner Einstellung! 💫",
            "Du bist nicht nur ein Agent - du bist ein Problemlöser! 🦅",
            "Kunden vergessen nie, wie du sie behandelt hast! 💖",
            "Professionell, freundlich, effizient - das bist du! ✨"
        ];
        
        this.dailyAchievements = {
            firstCall: "Der Tag kann kommen! Erster Anruf gemeistert! 🌅",
            fiveCallsToday: "Fünf Anrufe geschafft - du bist in Fahrt! 🚀",
            tenCallsToday: "Zehn Anrufe - du bist heute on fire! 🔥",
            firstAppointment: "Erstes Termin heute! Du rockst! 🏆",
            threeAppointments: "Drei Termine heute - Service-Superheld! 🦋",
            perfectCall: "Perfekter Anruf: Lang, erfolgreich, Termin! 🎆",
            speedDemon: "Speed-Champion: Unter 1 Minute, aber effektiv! ⚡",
            consultant: "Beratungs-Profi: Über 5 Minuten ausführlich! 🧠"
        };
        
        this.init();
    }
    
    init() {
        console.log('🎨 Modern Call Interactions: Initialisierung...');
        
        this.enhanceCallCards();
        this.setupCopyInteractions();
        this.setupAudioPlayerDelights();
        this.setupActionButtonInteractions();
        this.setupEasterEggs();
        this.setupKeyboardShortcuts();
        this.setupRealTimeDelights();
        this.setupSuccessAnimations();
        this.setupSoundToggle();
        
        // Beobachte DOM-Änderungen für dynamisch geladenen Inhalt
        this.observeDOMChanges();
        
        console.log('✅ Modern Call Interactions: Bereit!');
    }
    
    enhanceCallCards() {
        const callCards = document.querySelectorAll('.fi-ta-record, [data-call-id]');
        
        callCards.forEach(card => {
            if (!card.classList.contains('call-card-enhanced')) {
                card.classList.add('call-card', 'call-card-enhanced');
                
                // Hover-Effekt mit Personality
                card.addEventListener('mouseenter', this.handleCardHover.bind(this));
                card.addEventListener('mouseleave', this.handleCardLeave.bind(this));
                
                // Click-Feedback
                card.addEventListener('click', this.handleCardClick.bind(this));
                
                // Success-Animation für Termine
                if (this.hasAppointment(card)) {
                    this.addSuccessIndicator(card);
                }
            }
        });
    }
    
    handleCardHover(e) {
        const card = e.currentTarget;
        
        // Subtiler Sound-Effekt (nur wenn aktiviert)
        if (this.soundEnabled) {
            this.playHoverSound();
        }
        
        // Status-specific Animations
        const status = this.getCallStatus(card);
        if (status === 'ongoing') {
            card.classList.add('call-status-live');
        }
        
        // Phone Number Reveal Tease
        const phoneNumber = card.querySelector('[data-phone-masked]');
        if (phoneNumber) {
            phoneNumber.classList.add('phone-number-masked');
        }
    }
    
    handleCardLeave(e) {
        const card = e.currentTarget;
        card.classList.remove('call-status-live');
        
        const phoneNumber = card.querySelector('[data-phone-masked]');
        if (phoneNumber) {
            phoneNumber.classList.remove('phone-number-masked');
        }
    }
    
    handleCardClick(e) {
        const card = e.currentTarget;
        
        // Celebration für erfolgreiche Termine
        if (this.hasAppointment(card)) {
            if (Math.random() < 0.4) { // 40% Chance für extra celebration
                this.triggerSuccessCelebration(card);
            }
            // Mini confetti für jeden Termin-Click
            this.triggerMiniConfetti(card);
        }
        
        // Ripple-Effekt
        this.createRippleEffect(e, card);
        
        // Motivational Message basierend auf Tageszeit
        if (Math.random() < 0.1) { // 10% Chance
            this.showMotivationalMessage();
        }
    }
    
    setupCopyInteractions() {
        // Phone Number Copy mit Delight
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-copy-phone], .fi-ta-cell-phone [data-copyable]')) {
                e.preventDefault();
                this.handlePhoneCopy(e.target);
            }
        });
    }
    
    handlePhoneCopy(element) {
        const phoneNumber = element.textContent || element.dataset.copyValue;
        
        // Kopiere in Zwischenablage
        navigator.clipboard.writeText(phoneNumber).then(() => {
            // Visual Feedback mit Animation
            element.classList.add('copy-success', 'pulse-animation');
            
            // Sound Feedback
            if (this.soundEnabled) {
                this.playCopySuccessSound();
            }
            
            // Celebration-specific messages
            const copyMessages = [
                'Telefonnummer kopiert! 📞 Zeit für professionellen Kontakt!',
                'Perfekt gesichert! ✨ Bereit für deutschen Service!',
                'Mission accomplished! 🎯 Nummer einsatzbereit!',
                'Kopiert! 🚀 Zeig ihnen was Service bedeutet!',
                'Gesichert! 💫 Lass deine Expertise sprechen!'
            ];
            
            this.showPersonalityToast(copyMessages[Math.floor(Math.random() * copyMessages.length)], 'success');
            
            // Kleine Confetti-Animation
            if (Math.random() < 0.3) {
                this.createMiniConfettiFromElement(element);
            }
            
            // Reset nach Animation
            setTimeout(() => {
                element.classList.remove('copy-success', 'pulse-animation');
            }, 2500);
        }).catch(err => {
            console.warn('Copy failed:', err);
            this.showPersonalityToast('Ups! 🤖 Copy-Funktion heute etwas müde - probier es nochmal!', 'warning');
        });
    }
    
    setupAudioPlayerDelights() {
        const audioPlayers = document.querySelectorAll('audio[data-call-recording]');
        
        audioPlayers.forEach(player => {
            const container = player.closest('.call-audio-player') || this.createAudioContainer(player);
            
            // Waveform Animation
            this.addAudioWaveform(container, player);
            
            // Enhanced Controls
            this.enhanceAudioControls(container, player);
            
            // Progress Visualization
            player.addEventListener('timeupdate', () => {
                this.updateAudioProgress(container, player);
            });
            
            // Play/Pause Delights
            player.addEventListener('play', () => {
                container.classList.add('playing');
                if (this.soundEnabled) {
                    this.playAudioStartSound();
                }
            });
            
            player.addEventListener('pause', () => {
                container.classList.remove('playing');
            });
        });
    }
    
    setupActionButtonInteractions() {
        // Phone Call Button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="call_customer"], .call-phone-btn')) {
                this.handlePhoneButtonClick(e);
            }
        });
        
        // Note Button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="add_note"], .call-note-btn')) {
                this.handleNoteButtonClick(e);
            }
        });
    }
    
    handlePhoneButtonClick(e) {
        const button = e.target.closest('button, a');
        button.classList.add('call-phone-btn');
        
        // Rotation Animation
        setTimeout(() => {
            button.style.transform = 'rotate(-3deg) scale(1.05)';
        }, 50);
        
        setTimeout(() => {
            button.style.transform = '';
            button.classList.remove('call-phone-btn');
        }, 300);
        
        // Sound Effect
        if (this.soundEnabled) {
            this.playPhoneSound();
        }
        
        // Encouraging Message
        this.showPersonalityToast('📞 Verbindung wird hergestellt... Viel Erfolg!', 'info');
    }
    
    handleNoteButtonClick(e) {
        const button = e.target.closest('button');
        button.classList.add('call-note-btn');
        
        // Note-specific Animation
        setTimeout(() => {
            button.style.transform = 'rotate(2deg) scale(1.05)';
        }, 50);
        
        setTimeout(() => {
            button.style.transform = '';
            button.classList.remove('call-note-btn');
        }, 300);
        
        // Helper Message
        this.showPersonalityToast('📝 Notiz-Modus aktiviert! Was möchtest du festhalten?', 'info');
    }
    
    setupEasterEggs() {
        // Konami Code Easter Egg
        document.addEventListener('keydown', (e) => {
            this.handleKonamiSequence(e);
        });
        
        // Double-Click auf Logo für Surprise
        const logo = document.querySelector('.fi-logo, [data-logo]');
        if (logo) {
            logo.addEventListener('dblclick', this.triggerLogoSurprise.bind(this));
        }
        
        // Triple-Click auf Call Stats für Fun Facts
        this.setupCallStatsEasterEgg();
    }
    
    handleKonamiSequence(e) {
        if (e.code === this.konamiSequence[this.konamiIndex]) {
            this.konamiIndex++;
            
            if (this.konamiIndex === this.konamiSequence.length) {
                this.triggerKonamiEasterEgg();
                this.konamiIndex = 0; // Reset
            }
        } else {
            this.konamiIndex = 0; // Reset bei falschem Key
        }
    }
    
    triggerKonamiEasterEgg() {
        // Zeige Easter Egg Message
        const easterEgg = document.createElement('div');
        easterEgg.className = 'call-resource-konami';
        easterEgg.innerHTML = '🎮 Konami Code entdeckt! Du bist ein echter Profi! 🚀';
        
        document.body.appendChild(easterEgg);
        
        // Konfetti-Explosion
        this.triggerConfettiExplosion();
        
        // Entferne nach Animation
        setTimeout(() => {
            easterEgg.remove();
        }, 3000);
        
        // Special Sound
        if (this.soundEnabled) {
            this.playKonamiSound();
        }
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Nur auf Call-Seiten aktiv
            if (!window.location.pathname.includes('call')) return;
            
            // Alt + C: Copy erste Telefonnummer
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                this.copyFirstPhoneNumber();
                this.showPersonalityToast('🚀 Profi-Shortcut! Nummer kopiert - du kennst die Tricks!', 'success');
            }
            
            // Alt + N: Neue Notiz
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                this.openNewNote();
                this.showPersonalityToast('📝 Keyboard-Ninja! Notiz-Modus aktiviert!', 'info');
            }
            
            // Alt + R: Refresh mit Style
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                this.refreshWithStyle();
            }
            
            // Alt + M: Show motivational message
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                this.showMotivationalMessage();
            }
            
            // Alt + S: Toggle sound
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                this.soundEnabled = !this.soundEnabled;
                localStorage.setItem('callResourceSoundEnabled', this.soundEnabled);
                this.showPersonalityToast(
                    this.soundEnabled ? '🔊 Sound aktiviert!' : '🔇 Sound deaktiviert',
                    'info'
                );
            }
        });
        
        // Show shortcut help on page load (once per session)
        if (!sessionStorage.getItem('shortcutsShown')) {
            setTimeout(() => {
                this.showShortcutHints();
                sessionStorage.setItem('shortcutsShown', 'true');
            }, 3000);
        }
    }
    
    setupRealTimeDelights() {
        // WebSocket Events für Live-Updates
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('calls')
                .listen('CallCreated', (e) => {
                    this.handleNewCallDelight(e.call);
                })
                .listen('CallUpdated', (e) => {
                    this.handleCallUpdateDelight(e.call);
                });
        }
        
        // Polling Fallback mit Delight
        this.setupPollingDelights();
    }
    
    handleNewCallDelight(call) {
        // Show notification mit Personality
        const callMessages = [
            `🔔 Neuer Anruf! Zeit zu glänzen! Kunde wartet auf dich!`,
            `✨ Frischer Wind! Neuer Anruf = neue Möglichkeiten!`,
            `🚀 Action-Zeit! Zeig was deutscher Service bedeutet!`,
            `📞 Showtime! Neuer Anruf - mach ihn zum Erfolg!`,
            `⚡ Live-Action! Kunde ruft an - Zeit für Profi-Service!`
        ];
        
        this.showPersonalityToast(
            callMessages[Math.floor(Math.random() * callMessages.length)],
            'info'
        );
        
        // Enhanced page pulse mit gradient
        document.body.classList.add('new-call-pulse', 'energy-boost');
        setTimeout(() => {
            document.body.classList.remove('new-call-pulse', 'energy-boost');
        }, 1500);
        
        // Sound notification mit mehr Personality
        if (this.soundEnabled) {
            this.playNewCallNotificationSound();
        }
        
        // Update call counter und check achievements
        this.updateDailyStats('calls');
    }
    
    setupSuccessAnimations() {
        // Beobachte Filament Success Notifications
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.matches('.fi-no-content')) {
                        this.enhanceSuccessNotification(node);
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    enhanceSuccessNotification(notification) {
        // Add celebration class
        notification.classList.add('note-success-toast');
        
        // Random celebration message
        const randomMessage = this.celebrationMessages[
            Math.floor(Math.random() * this.celebrationMessages.length)
        ];
        
        // Replace content with personality
        const content = notification.querySelector('.fi-no-title, .fi-no-body');
        if (content && content.textContent.includes('gespeichert')) {
            content.innerHTML = `<span class=\"personality-message success\">${randomMessage}</span>`;
        }
        
        // Trigger confetti for special occasions
        if (Math.random() < 0.1) { // 10% chance
            setTimeout(() => this.triggerMiniConfetti(notification), 300);
        }
    }
    
    setupSoundToggle() {
        // Add sound toggle button to page
        const soundToggle = document.createElement('button');
        soundToggle.innerHTML = this.soundEnabled ? '🔊' : '🔇';
        soundToggle.className = 'sound-toggle-btn fixed bottom-4 right-4 w-12 h-12 bg-primary-500 text-white rounded-full shadow-lg hover:shadow-xl transition-all z-50';
        soundToggle.title = this.soundEnabled ? 'Sound deaktivieren' : 'Sound aktivieren';
        
        soundToggle.addEventListener('click', () => {
            this.soundEnabled = !this.soundEnabled;
            soundToggle.innerHTML = this.soundEnabled ? '🔊' : '🔇';
            soundToggle.title = this.soundEnabled ? 'Sound deaktivieren' : 'Sound aktivieren';
            
            localStorage.setItem('callResourceSoundEnabled', this.soundEnabled);
            
            this.showPersonalityToast(
                this.soundEnabled ? '🔊 Sound-Effekte aktiviert!' : '🔇 Sound-Effekte deaktiviert',
                'info'
            );
        });
        
        document.body.appendChild(soundToggle);
    }
    
    // Helper Methods
    getSoundPreference() {
        return localStorage.getItem('callResourceSoundEnabled') !== 'false';
    }
    
    hasAppointment(card) {
        return card.querySelector('.fi-badge-success, [data-appointment="true"]') !== null;
    }
    
    getCallStatus(card) {
        const statusBadge = card.querySelector('.fi-badge');
        if (!statusBadge) return 'unknown';
        
        if (statusBadge.textContent.includes('Laufend')) return 'ongoing';
        if (statusBadge.textContent.includes('Beendet')) return 'completed';
        if (statusBadge.textContent.includes('Fehler')) return 'error';
        
        return 'unknown';
    }
    
    addSuccessIndicator(card) {
        if (!card.querySelector('.appointment-success-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'appointment-success-indicator absolute top-2 right-2 w-3 h-3 bg-green-500 rounded-full animate-pulse';
            card.appendChild(indicator);
        }
    }
    
    triggerSuccessCelebration(element) {
        element.classList.add('call-success-celebration');
        
        setTimeout(() => {
            element.classList.remove('call-success-celebration');
        }, 800);
        
        if (this.soundEnabled) {
            this.playSuccessSound();
        }
    }
    
    createRippleEffect(e, element) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.className = 'ripple absolute bg-primary-400 bg-opacity-30 rounded-full pointer-events-none';
        ripple.style.animation = 'ripple-effect 0.6s linear';
        
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
    
    showPersonalityToast(message, type = 'info') {
        // Check if Filament notifications are available
        if (typeof window.filamentNotifications !== 'undefined') {
            window.filamentNotifications.show({
                title: message,
                type: type,
                duration: 3000
            });
            return;
        }
        
        // Fallback: Custom toast
        const toast = document.createElement('div');
        toast.className = `personality-toast fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'warning' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        toast.innerHTML = `<span class="personality-message ${type}">${message}</span>`;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('animate-fade-in'), 10);
        
        // Remove after delay
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Sound Methods (using Web Audio API for subtle effects)
    playHoverSound() {
        this.playTone(800, 0.03, 0.05);
    }
    
    playCopySuccessSound() {
        // Uplifting chord progression
        this.playChord([523, 659, 784], 0.4, 0.12); // C-E-G major chord
    }
    
    playSuccessSound() {
        this.playChord([523, 659, 784], 0.3, 0.2); // C-E-G chord
    }
    
    playPhoneSound() {
        // Simulate phone dial tone
        this.playTone(941, 0.1, 0.1); // DTMF high frequency
        setTimeout(() => this.playTone(1336, 0.1, 0.1), 100);
    }
    
    playNewCallNotificationSound() {
        // More exciting new call sound
        this.playChord([440, 554, 659, 880], 0.6, 0.15); // A-C#-E-A chord
    }
    
    playAchievementSound() {
        // Victory fanfare
        const notes = [523, 659, 784, 1047]; // C-E-G-C octave
        notes.forEach((freq, i) => {
            setTimeout(() => this.playTone(freq, 0.3, 0.1), i * 150);
        });
    }
    
    playMotivationalSound() {
        // Gentle motivational chime
        this.playChord([880, 1108, 1319], 0.8, 0.08); // A-C#-E high
    }
    
    playKonamiSound() {
        // Play ascending scale with more flair
        const scale = [261, 294, 330, 349, 392, 440, 494, 523, 587, 659, 784];
        scale.forEach((freq, i) => {
            setTimeout(() => this.playTone(freq, 0.2, 0.08), i * 80);
        });
    }
    
    playTone(frequency, duration, volume = 0.1) {
        if (!this.soundEnabled) return;
        
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
            gainNode.gain.linearRampToValueAtTime(volume, audioCtx.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            
            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + duration);
        } catch (error) {
            console.warn('Audio playback failed:', error);
        }
    }
    
    playChord(frequencies, duration, volume = 0.1) {
        frequencies.forEach(freq => this.playTone(freq, duration, volume / frequencies.length));
    }
    
    observeDOMChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    // Re-enhance new call cards
                    setTimeout(() => this.enhanceCallCards(), 100);
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Additional Easter Egg Methods
    triggerConfettiExplosion() {
        // Create multiple confetti pieces
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                this.createConfettiPiece();
            }, i * 20);
        }
    }
    
    createConfettiPiece() {
        const confetti = document.createElement('div');
        confetti.style.position = 'fixed';
        confetti.style.width = '8px';
        confetti.style.height = '8px';
        confetti.style.backgroundColor = [
            '#10b981', // success green
            '#3b82f6', // primary blue  
            '#f59e0b', // warning amber
            '#8b5cf6', // purple
            '#ef4444', // red
            '#06b6d4', // cyan
            '#84cc16'  // lime
        ][Math.floor(Math.random() * 7)];
        confetti.style.left = Math.random() * window.innerWidth + 'px';
        confetti.style.top = '-15px';
        confetti.style.zIndex = '9999';
        confetti.style.pointerEvents = 'none';
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        confetti.style.animation = `confetti-fall ${2 + Math.random() * 3}s ease-out forwards`;
        confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
        
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 5000);
    }
    
    // New delightful methods
    showMotivationalMessage() {
        const message = this.motivationalQuotes[Math.floor(Math.random() * this.motivationalQuotes.length)];
        this.showPersonalityToast(message, 'info');
        
        if (this.soundEnabled) {
            this.playMotivationalSound();
        }
    }
    
    updateDailyStats(type) {
        const today = new Date().toDateString();
        let stats = JSON.parse(localStorage.getItem('dailyCallStats') || '{}');
        
        if (!stats[today]) {
            stats[today] = { calls: 0, appointments: 0 };
        }
        
        stats[today][type]++;
        localStorage.setItem('dailyCallStats', JSON.stringify(stats));
        
        // Check for achievements
        this.checkDailyAchievements(stats[today]);
    }
    
    checkDailyAchievements(todayStats) {
        const { calls, appointments } = todayStats;
        
        // First call of the day
        if (calls === 1) {
            this.triggerAchievement('firstCall');
        }
        
        // Call milestones
        if (calls === 5) {
            this.triggerAchievement('fiveCallsToday');
        }
        
        if (calls === 10) {
            this.triggerAchievement('tenCallsToday');
        }
        
        // Appointment milestones
        if (appointments === 1) {
            this.triggerAchievement('firstAppointment');
        }
        
        if (appointments === 3) {
            this.triggerAchievement('threeAppointments');
        }
    }
    
    triggerAchievement(type) {
        const message = this.dailyAchievements[type];
        if (!message) return;
        
        // Show achievement notification
        this.showPersonalityToast(message, 'success');
        
        // Visual celebration
        this.triggerConfettiExplosion();
        
        // Sound effect
        if (this.soundEnabled) {
            this.playAchievementSound();
        }
        
        // Don't repeat same achievement today
        const today = new Date().toDateString();
        let shownAchievements = JSON.parse(localStorage.getItem('shownAchievements') || '{}');
        if (!shownAchievements[today]) shownAchievements[today] = [];
        if (!shownAchievements[today].includes(type)) {
            shownAchievements[today].push(type);
            localStorage.setItem('shownAchievements', JSON.stringify(shownAchievements));
        }
    }
    
    createMiniConfettiFromElement(element) {
        const rect = element.getBoundingClientRect();
        
        for (let i = 0; i < 8; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '4px';
                confetti.style.height = '4px';
                confetti.style.backgroundColor = ['#10b981', '#3b82f6', '#f59e0b'][Math.floor(Math.random() * 3)];
                confetti.style.left = (rect.left + rect.width/2) + (Math.random() - 0.5) * 40 + 'px';
                confetti.style.top = (rect.top + rect.height/2) + 'px';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';
                confetti.style.borderRadius = '50%';
                confetti.style.animation = `mini-confetti-burst 1s ease-out forwards`;
                
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 1000);
            }, i * 50);
        }
    }
    
    showShortcutHints() {
        const hints = [
            'Profi-Tipp: Alt+C kopiert die erste Telefonnummer!',
            'Power-User: Alt+N öffnet schnell eine Notiz!',
            'Geheim-Trick: Alt+M zeigt motivierende Nachrichten!',
            'Shortcut-Meister: Alt+S schaltet Sounds an/aus!'
        ];
        
        this.showPersonalityToast(
            hints[Math.floor(Math.random() * hints.length)] + ' 🤓',
            'info'
        );
    }
}

// CSS Animations für JavaScript-generierte Elemente
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple-effect {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes new-call-pulse {
        0% { background-color: transparent; }
        25% { background-color: rgba(16, 185, 129, 0.08); }
        50% { background-color: rgba(59, 130, 246, 0.05); }
        75% { background-color: rgba(16, 185, 129, 0.08); }
        100% { background-color: transparent; }
    }
    
    .new-call-pulse {
        animation: new-call-pulse 1.5s ease-in-out;
    }
    
    .energy-boost {
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
    }
    
    .pulse-animation {
        animation: gentle-pulse 0.6s ease-in-out;
    }
    
    @keyframes gentle-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .celebration-hover:hover {
        animation: celebration-bounce 0.3s ease-in-out;
    }
    
    @keyframes celebration-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    
    .celebration-pulse {
        animation: success-pulse 1s infinite;
    }
    
    @keyframes success-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.1); }
    }
    
    @keyframes confetti-fall {
        to {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    @keyframes mini-confetti-burst {
        0% {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        100% {
            transform: scale(0.3) translateY(-40px) translateX(var(--random-x, 0px));
            opacity: 0;
        }
    }
    
    .animate-fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
    
    .sound-toggle-btn:hover {
        transform: scale(1.1) rotate(5deg);
    }
    
    .hover-celebration:hover {
        animation: hover-joy 0.4s ease-in-out;
    }
    
    @keyframes hover-joy {
        0%, 100% { transform: scale(1) rotate(0deg); }
        25% { transform: scale(1.05) rotate(1deg); }
        75% { transform: scale(1.05) rotate(-1deg); }
    }
    
    .note-action-glow:hover {
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        animation: glow-pulse 0.5s ease-in-out;
    }
    
    @keyframes glow-pulse {
        0%, 100% { filter: brightness(1); }
        50% { filter: brightness(1.2); }
    }
`;
document.head.appendChild(style);

// Auto-initialize wenn DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ModernCallInteractions = ModernCallInteractions;
        if (window.location.pathname.includes('call')) {
            new ModernCallInteractions();
        }
    });
} else {
    window.ModernCallInteractions = ModernCallInteractions;
    if (window.location.pathname.includes('call')) {
        new ModernCallInteractions();
    }
}

export default ModernCallInteractions;