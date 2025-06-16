<div>
    @if($showTutorial && $currentTutorial)
        <div class="tutorial-overlay" wire:key="tutorial-{{ $currentTutorial['id'] }}">
            @if($currentTutorial['type'] === 'tooltip')
                <div class="tutorial-tooltip" 
                     x-data="tooltipTutorial()"
                     x-init="initTooltip('{{ $currentTutorial['target_selector'] }}')"
                     x-show="show"
                     x-transition>
                    <div class="tutorial-tooltip-content">
                        <div class="tutorial-header">
                            <h3>{{ $currentTutorial['title'] }}</h3>
                            <button wire:click="closeTutorial" class="tutorial-close">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                        <p>{{ $currentTutorial['description'] }}</p>
                        <div class="tutorial-actions">
                            <button wire:click="skipAll" class="tutorial-skip">
                                Alle überspringen
                            </button>
                            <button wire:click="markCompleted" class="tutorial-complete">
                                Verstanden
                            </button>
                        </div>
                    </div>
                    <div class="tutorial-arrow" x-ref="arrow"></div>
                </div>
            @elseif($currentTutorial['type'] === 'tour')
                <div class="tutorial-tour-overlay">
                    <div class="tutorial-tour-backdrop" wire:click="closeTutorial"></div>
                    <div class="tutorial-tour-content" 
                         x-data="tourTutorial(@js($currentTutorial['config']['steps'] ?? []))"
                         x-init="initTour()">
                        <div class="tutorial-header">
                            <h3>{{ $currentTutorial['title'] }}</h3>
                            <span class="tutorial-step-counter">
                                Schritt <span x-text="currentStep + 1"></span> von <span x-text="steps.length"></span>
                            </span>
                        </div>
                        <p x-text="steps[currentStep]?.content"></p>
                        <div class="tutorial-actions">
                            <button x-show="currentStep > 0" @click="previousStep" class="tutorial-prev">
                                Zurück
                            </button>
                            <button wire:click="skipAll" class="tutorial-skip">
                                Tour beenden
                            </button>
                            <button x-show="currentStep < steps.length - 1" @click="nextStep" class="tutorial-next">
                                Weiter
                            </button>
                            <button x-show="currentStep === steps.length - 1" wire:click="markCompleted" class="tutorial-complete">
                                Abschließen
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($currentTutorial['type'] === 'video')
                <div class="tutorial-modal-overlay">
                    <div class="tutorial-modal-backdrop" wire:click="closeTutorial"></div>
                    <div class="tutorial-modal">
                        <div class="tutorial-header">
                            <h3>{{ $currentTutorial['title'] }}</h3>
                            <button wire:click="closeTutorial" class="tutorial-close">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="tutorial-video">
                            <video controls>
                                <source src="{{ $currentTutorial['config']['video_url'] ?? '' }}" type="video/mp4">
                                Ihr Browser unterstützt das Video-Tag nicht.
                            </video>
                        </div>
                        <p>{{ $currentTutorial['description'] }}</p>
                        <div class="tutorial-actions">
                            <button wire:click="markCompleted" class="tutorial-complete">
                                Video angesehen
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Tutorial Progress Widget --}}
    @if(count($tutorials) > 0 && !session('tutorials_disabled'))
        <div class="tutorial-progress-widget">
            <div class="tutorial-progress-icon" wire:click="$set('showProgress', true)">
                <x-heroicon-o-academic-cap class="w-6 h-6" />
                @if($tutorialProgress['completion_percentage'] < 100)
                    <span class="tutorial-progress-badge">{{ $tutorialProgress['completed'] }}/{{ $tutorialProgress['total'] }}</span>
                @endif
            </div>
        </div>
    @endif

    @push('styles')
    <style>
        .tutorial-overlay {
            position: fixed;
            z-index: 9999;
        }
        
        .tutorial-tooltip {
            position: absolute;
            background: white;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 320px;
            z-index: 10000;
        }
        
        .tutorial-tooltip-content {
            position: relative;
        }
        
        .tutorial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .tutorial-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        
        .tutorial-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #6B7280;
        }
        
        .tutorial-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        
        .tutorial-skip {
            background: none;
            border: 1px solid #E5E7EB;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            color: #6B7280;
        }
        
        .tutorial-complete {
            background: #3B82F6;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .tutorial-arrow {
            position: absolute;
            width: 0;
            height: 0;
            border-style: solid;
        }
        
        .tutorial-tour-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
        }
        
        .tutorial-tour-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .tutorial-tour-content {
            position: absolute;
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .tutorial-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tutorial-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
        }
        
        .tutorial-modal {
            position: relative;
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .tutorial-video video {
            width: 100%;
            border-radius: 8px;
            margin: 16px 0;
        }
        
        .tutorial-progress-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }
        
        .tutorial-progress-icon {
            width: 56px;
            height: 56px;
            background: #3B82F6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            position: relative;
        }
        
        .tutorial-progress-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #EF4444;
            color: white;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .dark .tutorial-tooltip,
        .dark .tutorial-tour-content,
        .dark .tutorial-modal {
            background: #1F2937;
            color: #F3F4F6;
        }
        
        .dark .tutorial-skip {
            border-color: #374151;
            color: #9CA3AF;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function tooltipTutorial() {
            return {
                show: false,
                initTooltip(selector) {
                    setTimeout(() => {
                        const target = document.querySelector(selector);
                        if (target) {
                            const rect = target.getBoundingClientRect();
                            const tooltip = this.$el;
                            
                            // Position tooltip near target element
                            tooltip.style.top = (rect.bottom + 10) + 'px';
                            tooltip.style.left = rect.left + 'px';
                            
                            // Ensure tooltip stays within viewport
                            const tooltipRect = tooltip.getBoundingClientRect();
                            if (tooltipRect.right > window.innerWidth) {
                                tooltip.style.left = (window.innerWidth - tooltipRect.width - 10) + 'px';
                            }
                            
                            // Highlight target element
                            target.style.position = 'relative';
                            target.style.zIndex = '9998';
                            target.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.5)';
                            target.style.borderRadius = '4px';
                            
                            this.show = true;
                        }
                    }, 100);
                }
            }
        }
        
        function tourTutorial(tourSteps) {
            return {
                steps: tourSteps,
                currentStep: 0,
                initTour() {
                    this.highlightStep();
                },
                nextStep() {
                    if (this.currentStep < this.steps.length - 1) {
                        this.currentStep++;
                        this.highlightStep();
                    }
                },
                previousStep() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        this.highlightStep();
                    }
                },
                highlightStep() {
                    // Remove previous highlights
                    document.querySelectorAll('.tutorial-highlight').forEach(el => {
                        el.classList.remove('tutorial-highlight');
                        el.style.position = '';
                        el.style.zIndex = '';
                        el.style.boxShadow = '';
                    });
                    
                    // Highlight current step target
                    const step = this.steps[this.currentStep];
                    if (step && step.target) {
                        const target = document.querySelector(step.target);
                        if (target) {
                            target.classList.add('tutorial-highlight');
                            target.style.position = 'relative';
                            target.style.zIndex = '9998';
                            target.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.5)';
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            }
        }
    </script>
    @endpush
</div>