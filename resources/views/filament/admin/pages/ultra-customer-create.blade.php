<x-filament-panels::page>
    <style>
        /* Ultra Customer Create Styles */
        .customer-create-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .dark .customer-create-container {
            background: var(--filament-gray-800, #1f2937);
        }

        .duplicate-checker {
            background: var(--filament-warning-50, #fffbeb);
            border: 1px solid var(--filament-warning-200, #fde68a);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: none;
        }

        .dark .duplicate-checker {
            background: var(--filament-warning-900/20);
            border-color: var(--filament-warning-700, #b45309);
        }

        .duplicate-checker.active {
            display: block;
        }

        .duplicate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-top: 0.5rem;
        }

        .dark .duplicate-item {
            background: var(--filament-gray-700, #374151);
        }

        .form-helper {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--filament-primary-600, #2563eb);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 50;
        }

        .form-helper:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        .quick-fill-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .quick-fill-btn {
            padding: 0.5rem 1rem;
            background: var(--filament-gray-100, #f3f4f6);
            border: 1px solid var(--filament-gray-300, #d1d5db);
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .quick-fill-btn {
            background: var(--filament-gray-700, #374151);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .quick-fill-btn:hover {
            background: var(--filament-primary-50, #eff6ff);
            border-color: var(--filament-primary-500, #3b82f6);
            color: var(--filament-primary-600, #2563eb);
        }

        .dark .quick-fill-btn:hover {
            background: var(--filament-primary-900/20);
            border-color: var(--filament-primary-500, #3b82f6);
        }

        .customer-type-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .type-option {
            border: 2px solid var(--filament-gray-300, #d1d5db);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .dark .type-option {
            background: var(--filament-gray-800, #1f2937);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .type-option:hover {
            border-color: var(--filament-primary-500, #3b82f6);
            transform: translateY(-2px);
        }

        .type-option.selected {
            border-color: var(--filament-primary-500, #3b82f6);
            background: var(--filament-primary-50, #eff6ff);
        }

        .dark .type-option.selected {
            background: var(--filament-primary-900/30);
        }

        .type-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .vip-benefits {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }

        .vip-benefits.active {
            display: block;
        }

        .address-autocomplete {
            position: relative;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--filament-gray-300, #d1d5db);
            border-radius: 8px;
            margin-top: 0.25rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 50;
            display: none;
        }

        .dark .autocomplete-suggestions {
            background: var(--filament-gray-800, #1f2937);
            border-color: var(--filament-gray-600, #4b5563);
        }

        .autocomplete-suggestions.active {
            display: block;
        }

        .suggestion-item {
            padding: 0.75rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .suggestion-item:hover {
            background: var(--filament-gray-100, #f3f4f6);
        }

        .dark .suggestion-item:hover {
            background: var(--filament-gray-700, #374151);
        }

        @media (max-width: 768px) {
            .customer-type-selector {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <div class="customer-create-container">
        <!-- Duplicate Checker Alert -->
        <div class="duplicate-checker" id="duplicate-checker">
            <h4 class="font-semibold mb-2 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-600" />
                Possible Duplicate Customer
            </h4>
            <p class="text-sm mb-3">We found existing customers with similar details:</p>
            <div id="duplicate-list"></div>
        </div>

        <!-- Quick Fill Buttons -->
        <div class="p-6 pb-0">
            <div class="quick-fill-buttons">
                <button type="button" class="quick-fill-btn" onclick="fillTestCustomer()">
                    <x-heroicon-o-beaker class="w-4 h-4 inline mr-1" />
                    Test Customer
                </button>
                <button type="button" class="quick-fill-btn" onclick="fillBusinessCustomer()">
                    <x-heroicon-o-building-office class="w-4 h-4 inline mr-1" />
                    Business Template
                </button>
                <button type="button" class="quick-fill-btn" onclick="clearForm()">
                    <x-heroicon-o-x-mark class="w-4 h-4 inline mr-1" />
                    Clear Form
                </button>
            </div>
        </div>

        <!-- Form Content -->
        <div class="p-6 pt-2">
            <x-filament-panels::form wire:submit="create">
                <!-- Customer Type Visual Selector -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Quick Customer Type Selection
                    </label>
                    <div class="customer-type-selector">
                        <div class="type-option" onclick="selectCustomerType('private')">
                            <div class="type-icon">👤</div>
                            <div class="font-medium">Private</div>
                            <div class="text-xs text-gray-500">Individual customer</div>
                        </div>
                        <div class="type-option" onclick="selectCustomerType('business')">
                            <div class="type-icon">🏢</div>
                            <div class="font-medium">Business</div>
                            <div class="text-xs text-gray-500">Company account</div>
                        </div>
                        <div class="type-option" onclick="selectCustomerType('vip')">
                            <div class="type-icon">⭐</div>
                            <div class="font-medium">VIP</div>
                            <div class="text-xs text-gray-500">Premium customer</div>
                        </div>
                        <div class="type-option" onclick="selectCustomerType('premium')">
                            <div class="type-icon">💎</div>
                            <div class="font-medium">Premium</div>
                            <div class="text-xs text-gray-500">Top-tier service</div>
                        </div>
                    </div>
                </div>

                <!-- VIP Benefits Display -->
                <div class="vip-benefits" id="vip-benefits">
                    <h4 class="font-semibold mb-2">🌟 VIP Benefits Activated</h4>
                    <ul class="text-sm space-y-1">
                        <li>• Priority appointment booking</li>
                        <li>• Extended appointment times</li>
                        <li>• Dedicated support line</li>
                        <li>• Exclusive offers and discounts</li>
                    </ul>
                </div>

                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>

    <!-- Form Helper Assistant -->
    <div class="form-helper" onclick="toggleAssistant()">
        <x-heroicon-o-sparkles class="w-5 h-5" />
        <span>Need help?</span>
    </div>

    <script>
        // Customer type selection
        function selectCustomerType(type) {
            // Update visual selection
            document.querySelectorAll('.type-option').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Update form field
            const typeSelect = document.querySelector('select[name="customer_type"]');
            if (typeSelect) {
                typeSelect.value = type;
                typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Show VIP benefits if applicable
            const vipBenefits = document.getElementById('vip-benefits');
            if (type === 'vip' || type === 'premium') {
                vipBenefits.classList.add('active');
                
                // Also check the VIP toggle
                const vipToggle = document.querySelector('input[name="is_vip"]');
                if (vipToggle) {
                    vipToggle.checked = true;
                    vipToggle.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } else {
                vipBenefits.classList.remove('active');
            }
        }

        // Test customer data
        function fillTestCustomer() {
            const fields = {
                name: 'Max Mustermann',
                email: 'max@example.com',
                phone: '+49 176 12345678',
                address_line_1: 'Musterstraße 123',
                city: 'Berlin',
                postal_code: '10115',
                country: 'DE'
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.querySelector(`input[name="${name}"]`) || 
                            document.querySelector(`select[name="${name}"]`);
                if (input) {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        // Business customer template
        function fillBusinessCustomer() {
            selectCustomerType('business');
            
            const fields = {
                name: 'Musterfirma GmbH',
                email: 'info@musterfirma.de',
                phone: '+49 30 12345678',
                address_line_1: 'Hauptstraße 1',
                city: 'Berlin',
                postal_code: '10117',
                country: 'DE'
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.querySelector(`input[name="${name}"]`) || 
                            document.querySelector(`select[name="${name}"]`);
                if (input) {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        // Clear form
        function clearForm() {
            document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            
            document.querySelectorAll('.type-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            document.getElementById('vip-benefits').classList.remove('active');
        }

        // Assistant toggle
        function toggleAssistant() {
            alert('AI Assistant coming soon! Will help with:\n\n• Auto-completing customer data\n• Suggesting customer type\n• Finding duplicates\n• Setting up initial appointments');
        }

        // Duplicate checking simulation
        let duplicateTimer;
        document.addEventListener('input', function(e) {
            if (e.target.name === 'phone' || e.target.name === 'email') {
                clearTimeout(duplicateTimer);
                duplicateTimer = setTimeout(() => {
                    checkForDuplicates(e.target.value);
                }, 500);
            }
        });

        function checkForDuplicates(value) {
            // This would normally make an API call
            // For demo, show duplicate checker for specific values
            const duplicateChecker = document.getElementById('duplicate-checker');
            const duplicateList = document.getElementById('duplicate-list');
            
            if (value.includes('12345')) {
                duplicateList.innerHTML = `
                    <div class="duplicate-item">
                        <div>
                            <p class="font-medium">Max Mustermann</p>
                            <p class="text-sm text-gray-500">+49 176 12345678 • max@example.com</p>
                        </div>
                        <button type="button" class="text-primary-600 hover:underline text-sm">View</button>
                    </div>
                `;
                duplicateChecker.classList.add('active');
            } else {
                duplicateChecker.classList.remove('active');
            }
        }
    </script>
</x-filament-panels::page>