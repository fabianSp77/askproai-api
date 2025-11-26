@extends('customer-portal.layouts.app', ['hideNavigation' => true])

@section('title', 'Einladung annehmen')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary to-purple-600 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white rounded-xl shadow-2xl p-8">
        <div>
            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-primary text-white">
                <i class="fas fa-user-plus text-2xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Willkommen!
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Erstellen Sie Ihr Kundenkonto, um Ihre Termine zu verwalten.
            </p>
        </div>

        <div x-data="invitationForm"
             data-token="{{ $token }}"
             x-init="initFromElement($el)"
             x-cloak>

            <!-- Loading State -->
            <div x-show="loading" class="text-center py-8">
                @include('customer-portal.components.loading-spinner')
                <p class="mt-4 text-gray-600">Einladung wird überprüft...</p>
            </div>

            <!-- Error State -->
            <div x-show="!loading && error" class="rounded-md bg-red-50 border border-red-200 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Fehler</h3>
                        <p class="mt-2 text-sm text-red-700" x-text="error"></p>
                        <div class="mt-4">
                            <a href="{{ url('/') }}" class="text-sm font-medium text-red-700 hover:text-red-600">
                                Zur Startseite <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <form x-show="!loading && !error && invitation"
                  @submit.prevent="submitForm"
                  class="mt-8 space-y-6">

                <!-- Invitation Context Banner -->
                <div class="bg-gradient-to-r from-primary/10 to-purple-100 rounded-lg p-4 border border-primary/20">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-hand-holding-heart text-primary text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold text-gray-900" x-text="invitation?.invited_by || 'Jemand'"></span>
                                hat Sie eingeladen, dem Kundenportal von
                                <span class="font-semibold text-primary" x-text="invitation?.company?.name || ''"></span>
                                beizutreten.
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Eingeladen am <span x-text="invitation ? new Date(invitation.invited_at).toLocaleDateString('de-DE', { day: 'numeric', month: 'long', year: 'numeric' }) : ''"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pre-filled Info (Read-only) -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-info-circle text-primary mr-1"></i>
                        Ihre Kontodaten:
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-envelope w-5 text-primary"></i>
                            <span class="ml-2" x-text="invitation ? invitation.email : ''"></span>
                        </div>
                        <div class="flex items-center text-gray-600" x-show="invitation && invitation.phone">
                            <i class="fas fa-phone w-5 text-primary"></i>
                            <span class="ml-2" x-text="invitation ? invitation.phone : ''"></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-building w-5 text-primary"></i>
                            <span class="ml-2" x-text="invitation && invitation.company ? invitation.company.name : ''"></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-user-tag w-5 text-primary"></i>
                            <span class="ml-2">Rolle: <span class="font-medium text-gray-900" x-text="invitation && invitation.role ? invitation.role.display_name : ''"></span></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Vollständiger Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               x-model="form.name"
                               required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                               :class="{ 'border-red-500': errors.name }"
                               placeholder="Max Mustermann">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            E-Mail-Adresse <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               x-model="form.email"
                               required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                               :class="{ 'border-red-500': errors.email }"
                               placeholder="max@beispiel.de">
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Passwort <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   x-model="form.password"
                                   required
                                   class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                   :class="{ 'border-red-500': errors.password }"
                                   placeholder="Mindestens 8 Zeichen">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas text-gray-400 hover:text-gray-600"
                                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600"></p>
                        <p class="mt-1 text-xs text-gray-500">Mindestens 8 Zeichen</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Passwort bestätigen <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPasswordConfirmation ? 'text' : 'password'"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   x-model="form.password_confirmation"
                                   required
                                   class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                   :class="{ 'border-red-500': errors.password_confirmation }"
                                   placeholder="Passwort wiederholen">
                            <button type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas text-gray-400 hover:text-gray-600"
                                   :class="showPasswordConfirmation ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Phone (Hidden, pre-filled) -->
                    <input type="hidden" name="phone" x-model="form.phone">

                    <!-- Terms Acceptance -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox"
                                   id="terms"
                                   name="terms"
                                   x-model="form.terms"
                                   required
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   :class="{ 'border-red-500': errors.terms }">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">
                                Ich akzeptiere die <a href="#" class="text-primary hover:text-primary-dark underline">Nutzungsbedingungen</a> und <a href="#" class="text-primary hover:text-primary-dark underline">Datenschutzbestimmungen</a> <span class="text-red-500">*</span>
                            </label>
                            <p x-show="errors.terms" x-text="errors.terms" class="mt-1 text-red-600"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            :disabled="submitting"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">
                            <i class="fas fa-check mr-2"></i>
                            Konto erstellen
                        </span>
                        <span x-show="submitting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Wird erstellt...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('invitationForm', () => ({
            token: '',
            invitation: null,
            loading: true,
            error: null,
            submitting: false,
            showPassword: false,
            showPasswordConfirmation: false,

            form: {
                name: '',
                email: '',
                phone: '',
                password: '',
                password_confirmation: '',
                terms: false
            },

            errors: {},

            initFromElement(el) {
                // Read token from data attribute to avoid Alpine timing issues
                const token = el.dataset.token;

                if (!token || token === 'undefined' || token === '') {
                    this.error = 'Ungültiger Einladungslink.';
                    this.loading = false;
                    return;
                }
                this.token = token;
                // Delay to ensure axios is fully initialized by parent app component
                setTimeout(() => this.validateToken(), 200);
            },

            async validateToken() {
                if (!this.token || this.token === 'undefined') {
                    this.error = 'Ungültiger Einladungslink.';
                    this.loading = false;
                    return;
                }

                this.loading = true;
                this.error = null;

                try {
                    const response = await axios.get(`/api/customer-portal/invitations/${this.token}/validate`);

                    if (response.data.success) {
                        this.invitation = response.data.data;
                        this.form.email = this.invitation.email || '';
                        this.form.phone = this.invitation.phone || '';
                    } else {
                        this.error = response.data.message || 'Einladung ungültig oder abgelaufen.';
                    }
                } catch (error) {
                    if (error.response?.status === 404) {
                        this.error = 'Diese Einladung wurde nicht gefunden oder ist bereits abgelaufen.';
                    } else if (error.response?.data?.message) {
                        this.error = error.response.data.message;
                    } else {
                        this.error = 'Die Einladung konnte nicht überprüft werden. Bitte versuchen Sie es später erneut.';
                    }
                } finally {
                    this.loading = false;
                }
            },

            async submitForm() {
                // Reset errors
                this.errors = {};

                // Client-side validation
                if (!this.validateForm()) {
                    return;
                }

                this.submitting = true;

                try {
                    const response = await axios.post(
                        `/api/customer-portal/invitations/${this.token}/accept`,
                        this.form
                    );

                    if (response.data.success) {
                        // Store token and user data (handle both response formats)
                        const token = response.data.access_token || response.data.data?.token;
                        const user = response.data.user || response.data.data?.user;

                        if (token && this.$root && typeof this.$root.login === 'function') {
                            // Use the parent app's login method if available
                            this.$root.login(token, user);
                        } else if (token) {
                            // Fallback: store token directly
                            localStorage.setItem('customer_portal_token', token);
                            if (user) {
                                localStorage.setItem('customer_portal_user', JSON.stringify(user));
                            }
                        }

                        // Show success message
                        if (this.$root && typeof this.$root.showToast === 'function') {
                            this.$root.showToast('Willkommen! Ihr Konto wurde erfolgreich erstellt.', 'success');
                        }

                        // Redirect to appointments page
                        setTimeout(() => {
                            window.location.href = '/meine-termine';
                        }, 1000);
                    } else {
                        const errorMsg = response.data.message || response.data.error || 'Ein Fehler ist aufgetreten.';
                        if (this.$root && typeof this.$root.showToast === 'function') {
                            this.$root.showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (error) {
                    console.error('Registration error:', error);

                    if (error.response?.data?.errors) {
                        // Laravel validation errors
                        this.errors = error.response.data.errors;
                        // Flatten array errors
                        Object.keys(this.errors).forEach(key => {
                            if (Array.isArray(this.errors[key])) {
                                this.errors[key] = this.errors[key][0];
                            }
                        });
                    } else if (error.response?.data?.message) {
                        this.$root.showToast(error.response.data.message, 'error');
                    } else {
                        this.$root.showToast('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
                    }
                } finally {
                    this.submitting = false;
                }
            },

            validateForm() {
                let isValid = true;

                // Name validation
                if (!this.form.name || this.form.name.trim().length < 2) {
                    this.errors.name = 'Bitte geben Sie Ihren vollständigen Namen ein (mindestens 2 Zeichen).';
                    isValid = false;
                }

                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!this.form.email || !emailRegex.test(this.form.email)) {
                    this.errors.email = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
                    isValid = false;
                }

                // Password validation
                if (!this.form.password || this.form.password.length < 8) {
                    this.errors.password = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
                    isValid = false;
                }

                // Password confirmation validation
                if (this.form.password !== this.form.password_confirmation) {
                    this.errors.password_confirmation = 'Die Passwörter stimmen nicht überein.';
                    isValid = false;
                }

                // Terms validation
                if (!this.form.terms) {
                    this.errors.terms = 'Sie müssen die Nutzungsbedingungen akzeptieren.';
                    isValid = false;
                }

                return isValid;
            }
        }));
    });
</script>
@endsection
