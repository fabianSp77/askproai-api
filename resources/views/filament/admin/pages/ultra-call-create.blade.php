<x-filament-panels::page>
    <style>
        /* Ultra Call Create Styles */
        .ultra-create-form {
            background: var(--filament-gray-50, #f9fafb);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark .ultra-create-form {
            background: var(--filament-gray-800, #1f2937);
        }

        .form-helper-text {
            font-size: 0.875rem;
            color: var(--filament-gray-500, #6b7280);
            margin-top: 0.5rem;
        }

        .smart-suggestions {
            background: var(--filament-primary-50, #eff6ff);
            border: 1px solid var(--filament-primary-200, #bfdbfe);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .dark .smart-suggestions {
            background: var(--filament-gray-700, #374151);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .suggestion-pill {
            display: inline-block;
            background: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--filament-gray-300, #d1d5db);
        }

        .dark .suggestion-pill {
            background: var(--filament-gray-600, #4b5563);
            border-color: var(--filament-gray-500, #6b7280);
        }

        .suggestion-pill:hover {
            background: var(--filament-primary-500, #3b82f6);
            color: white;
            transform: translateY(-1px);
        }

        .form-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--filament-gray-300, #d1d5db);
        }

        .progress-step:last-child::after {
            display: none;
        }

        .progress-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--filament-gray-300, #d1d5db);
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .progress-step.active .progress-circle {
            background: var(--filament-primary-500, #3b82f6);
            color: white;
        }

        .progress-step.completed .progress-circle {
            background: var(--filament-success-500, #10b981);
            color: white;
        }

        @media (max-width: 768px) {
            .form-progress {
                display: none;
            }
        }
    </style>

    <div class="ultra-create-form">
        <!-- Progress Indicator -->
        <div class="form-progress">
            <div class="progress-step active" id="step-1">
                <div class="progress-circle">1</div>
                <span class="text-sm">Call Info</span>
            </div>
            <div class="progress-step" id="step-2">
                <div class="progress-circle">2</div>
                <span class="text-sm">Details</span>
            </div>
            <div class="progress-step" id="step-3">
                <div class="progress-circle">3</div>
                <span class="text-sm">Recording</span>
            </div>
        </div>

        <!-- Smart Suggestions -->
        <div class="smart-suggestions" x-data="{ show: true }" x-show="show" x-collapse>
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-semibold flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500" />
                    Smart Suggestions
                </h4>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Click any suggestion to auto-fill the form
            </p>
            <div class="suggestions-container">
                <span class="suggestion-pill" onclick="fillCustomer('John Doe', '+49 123 456789')">
                    <x-heroicon-o-user class="w-4 h-4 inline mr-1" />
                    Recent: John Doe
                </span>
                <span class="suggestion-pill" onclick="fillDuration(180)">
                    <x-heroicon-o-clock class="w-4 h-4 inline mr-1" />
                    Avg Duration: 3 min
                </span>
                <span class="suggestion-pill" onclick="fillSentiment('positive')">
                    <x-heroicon-o-face-smile class="w-4 h-4 inline mr-1" />
                    Positive Call
                </span>
            </div>
        </div>

        <!-- Form Content -->
        <x-filament-panels::form wire:submit="create">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>

        <!-- Helper Text -->
        <div class="form-helper-text mt-4">
            <p class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-4 h-4" />
                Tip: Use keyboard shortcuts - Tab to navigate, Enter to submit
            </p>
        </div>
    </div>

    <script>
        // Auto-fill functions
        function fillCustomer(name, phone) {
            // Find and fill customer fields
            const nameInput = document.querySelector('input[name*="customer"]');
            const phoneInput = document.querySelector('input[name*="phone"]');
            
            if (nameInput) {
                nameInput.value = name;
                nameInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            if (phoneInput) {
                phoneInput.value = phone;
                phoneInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        function fillDuration(seconds) {
            const durationInput = document.querySelector('input[name*="duration"]');
            if (durationInput) {
                durationInput.value = seconds;
                durationInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        function fillSentiment(sentiment) {
            const sentimentSelect = document.querySelector('select[name*="sentiment"]');
            if (sentimentSelect) {
                sentimentSelect.value = sentiment;
                sentimentSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Progress tracking
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('[data-section]');
            
            sections.forEach((section, index) => {
                section.addEventListener('focus', function(e) {
                    updateProgress(index + 1);
                }, true);
            });
        });

        function updateProgress(step) {
            const steps = document.querySelectorAll('.progress-step');
            
            steps.forEach((stepEl, index) => {
                if (index < step - 1) {
                    stepEl.classList.add('completed');
                    stepEl.classList.remove('active');
                } else if (index === step - 1) {
                    stepEl.classList.add('active');
                    stepEl.classList.remove('completed');
                } else {
                    stepEl.classList.remove('active', 'completed');
                }
            });
        }
    </script>
</x-filament-panels::page>