@extends('portal.layouts.alpine-app')

@section('title', 'Hybrid Alpine + React Example')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Hybrid Alpine.js + React Components</h1>
    
    <!-- Alpine.js Component -->
    <div class="mb-8 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Alpine.js Interactive Form</h2>
        
        <div x-data="{ 
                formData: {
                    name: '',
                    email: '',
                    branch: null,
                    date: null
                },
                errors: {},
                loading: false,
                success: false,
                
                async submitForm() {
                    this.loading = true;
                    this.errors = {};
                    this.success = false;
                    
                    try {
                        const response = await Alpine.store('portal').post('/api/contact', this.formData);
                        this.success = true;
                        this.formData = { name: '', email: '', branch: null, date: null };
                        
                        // Notify React component via custom event
                        window.dispatchEvent(new CustomEvent('alpine-form-submitted', {
                            detail: response.data
                        }));
                        
                        // Show toast
                        Alpine.store('portal').showToast('Formular erfolgreich gesendet!', 'success');
                    } catch (error) {
                        if (error.response?.status === 422) {
                            this.errors = error.response.data.errors;
                        } else {
                            Alpine.store('portal').showToast('Ein Fehler ist aufgetreten', 'error');
                        }
                    } finally {
                        this.loading = false;
                    }
                }
             }">
            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" 
                           x-model="formData.name"
                           :class="{ 'border-red-300': errors.name }"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <p x-show="errors.name" x-text="errors.name?.[0]" class="mt-1 text-sm text-red-600"></p>
                </div>
                
                <!-- Email Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" 
                           x-model="formData.email"
                           :class="{ 'border-red-300': errors.email }"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <p x-show="errors.email" x-text="errors.email?.[0]" class="mt-1 text-sm text-red-600"></p>
                </div>
                
                <!-- Branch Selector (Alpine Component) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filiale</label>
                    <div x-data="branchSelector" class="relative">
                        <button type="button"
                                @click="open = !open"
                                class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <span x-text="formData.branch ? branches.find(b => b.id === formData.branch)?.name : 'Filiale wählen'"></span>
                            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>
                        
                        <div x-show="open"
                             x-transition
                             @click.away="open = false"
                             class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                            <template x-for="branch in branches" :key="branch.id">
                                <button type="button"
                                        @click="formData.branch = branch.id; open = false"
                                        class="w-full text-left cursor-default select-none relative py-2 pl-3 pr-9 hover:bg-gray-50"
                                        :class="{ 'text-white bg-blue-600': formData.branch === branch.id }">
                                    <span x-text="branch.name" :class="{ 'font-semibold': formData.branch === branch.id }"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                
                <!-- Date Picker (Alpine Component) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                    <div x-data="datepicker" 
                         x-init="$watch('value', val => formData.date = val)"
                         class="relative">
                        <input type="text"
                               x-model="displayValue"
                               @click="toggle()"
                               readonly
                               placeholder="Datum wählen..."
                               :class="{ 'border-red-300': errors.date }"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                        
                        <!-- Datepicker dropdown would appear here -->
                    </div>
                    <p x-show="errors.date" x-text="errors.date?.[0]" class="mt-1 text-sm text-red-600"></p>
                </div>
                
                <!-- Submit Button -->
                <div class="flex items-center justify-between">
                    <button type="submit"
                            :disabled="loading"
                            :class="{ 'opacity-50 cursor-not-allowed': loading }"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Wird gesendet...' : 'Absenden'"></span>
                    </button>
                    
                    <div x-show="success" 
                         x-transition
                         class="text-sm text-green-600 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Erfolgreich gesendet!
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- React Component Mount Point -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">React Data Table Component</h2>
        <div id="react-data-table"></div>
    </div>
    
    <!-- Communication Example -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-2">Alpine ↔ React Communication</h3>
        <p class="text-sm text-blue-700 mb-4">
            Diese Seite demonstriert, wie Alpine.js und React zusammenarbeiten können:
        </p>
        <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
            <li>Alpine.js Form sendet Daten und triggert ein Custom Event</li>
            <li>React Component hört auf das Event und aktualisiert die Tabelle</li>
            <li>Beide nutzen den gleichen API-Endpoint und Portal Store</li>
            <li>WebSocket Updates werden von beiden empfangen</li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Bridge between Alpine and React
document.addEventListener('alpine:init', () => {
    // Make Alpine store available globally for React
    window.AlpinePortalStore = Alpine.store('portal');
});

// Initialize React component after Alpine
document.addEventListener('DOMContentLoaded', () => {
    // React component would be mounted here
    // Example: ReactDOM.render(<DataTable />, document.getElementById('react-data-table'));
    
    // For demonstration, we'll just add a placeholder
    const reactContainer = document.getElementById('react-data-table');
    if (reactContainer) {
        reactContainer.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                </svg>
                <p class="mt-2">React Data Table Component würde hier geladen werden</p>
                <p class="text-xs mt-1">Hört auf 'alpine-form-submitted' Events</p>
            </div>
        `;
    }
});

// Example of listening to Alpine events from vanilla JS/React
window.addEventListener('alpine-form-submitted', (event) => {
    console.log('Form submitted from Alpine:', event.detail);
    // React component would update its data here
});
</script>
@endpush