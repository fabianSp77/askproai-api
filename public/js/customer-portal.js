/**
 * Customer Portal JavaScript Utilities
 *
 * Provides helper functions for the customer portal frontend.
 * Used in conjunction with Alpine.js for state management.
 */

// API Client Configuration
const CustomerPortalAPI = {
    baseURL: window.location.origin,

    /**
     * Get authentication headers
     */
    getHeaders() {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        // Add auth token
        const token = localStorage.getItem('customer_token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        return headers;
    },

    /**
     * Make API request
     */
    async request(method, endpoint, data = null) {
        const url = `${this.baseURL}${endpoint}`;
        const options = {
            method: method.toUpperCase(),
            headers: this.getHeaders(),
        };

        if (data && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method)) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const responseData = await response.json();

            if (!response.ok) {
                throw {
                    response: {
                        status: response.status,
                        data: responseData
                    }
                };
            }

            return { data: responseData };
        } catch (error) {
            throw error;
        }
    },

    // Convenience methods
    get(endpoint) {
        return this.request('GET', endpoint);
    },

    post(endpoint, data) {
        return this.request('POST', endpoint, data);
    },

    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    },

    delete(endpoint, data) {
        return this.request('DELETE', endpoint, data);
    }
};

// Date and Time Utilities
const DateTimeUtils = {
    /**
     * Format date according to German locale
     */
    formatDate(dateString, format = 'long') {
        if (!dateString) return '';
        const date = new Date(dateString);

        const formats = {
            'long': {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            },
            'short': {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            },
            'weekday': {
                weekday: 'long'
            },
            'day': null, // Special case
            'short-month': null, // Special case
            'datetime': {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }
        };

        if (format === 'day') {
            return date.getDate();
        }

        if (format === 'short-month') {
            return date.toLocaleDateString('de-DE', { month: 'short' }).toUpperCase();
        }

        const formatOptions = formats[format] || formats.long;
        return date.toLocaleDateString('de-DE', formatOptions);
    },

    /**
     * Format time (HH:MM)
     */
    formatTime(timeString) {
        if (!timeString) return '';
        const [hours, minutes] = timeString.split(':');
        return `${hours}:${minutes}`;
    },

    /**
     * Check if date is today
     */
    isToday(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        return date.toDateString() === today.toDateString();
    },

    /**
     * Check if date is tomorrow
     */
    isTomorrow(dateString) {
        const date = new Date(dateString);
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return date.toDateString() === tomorrow.toDateString();
    },

    /**
     * Get relative date label (Heute, Morgen, or formatted date)
     */
    getRelativeDateLabel(dateString) {
        if (this.isToday(dateString)) return 'Heute';
        if (this.isTomorrow(dateString)) return 'Morgen';
        return this.formatDate(dateString, 'long');
    },

    /**
     * Calculate duration in minutes between two times
     */
    calculateDuration(startTime, endTime) {
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);

        const startTotal = startHours * 60 + startMinutes;
        const endTotal = endHours * 60 + endMinutes;

        return endTotal - startTotal;
    }
};

// Validation Utilities
const ValidationUtils = {
    /**
     * Validate email address
     */
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validate phone number (German format)
     */
    isValidPhone(phone) {
        const regex = /^(\+49|0)[1-9]\d{1,14}$/;
        return regex.test(phone.replace(/\s/g, ''));
    },

    /**
     * Validate password strength
     */
    isValidPassword(password, minLength = 8) {
        return password && password.length >= minLength;
    },

    /**
     * Check if passwords match
     */
    passwordsMatch(password, confirmation) {
        return password === confirmation;
    },

    /**
     * Validate required field
     */
    isRequired(value) {
        return value !== null && value !== undefined && value.toString().trim().length > 0;
    }
};

// Status and Label Utilities
const StatusUtils = {
    /**
     * Get status text in German
     */
    getStatusText(status) {
        const statusMap = {
            'confirmed': 'Bestätigt',
            'pending': 'Ausstehend',
            'cancelled': 'Storniert',
            'completed': 'Abgeschlossen',
            'rescheduled': 'Umgebucht'
        };
        return statusMap[status] || status;
    },

    /**
     * Get status color class
     */
    getStatusColor(status) {
        const colorMap = {
            'confirmed': 'green',
            'pending': 'yellow',
            'cancelled': 'red',
            'completed': 'gray',
            'rescheduled': 'blue'
        };
        return colorMap[status] || 'gray';
    },

    /**
     * Get status icon
     */
    getStatusIcon(status) {
        const iconMap = {
            'confirmed': 'fa-check-circle',
            'pending': 'fa-clock',
            'cancelled': 'fa-times-circle',
            'completed': 'fa-calendar-check',
            'rescheduled': 'fa-calendar-alt'
        };
        return iconMap[status] || 'fa-question-circle';
    }
};

// Local Storage Utilities
const StorageUtils = {
    /**
     * Save to local storage with JSON serialization
     */
    set(key, value) {
        try {
            const serialized = JSON.stringify(value);
            localStorage.setItem(key, serialized);
            return true;
        } catch (error) {
            console.error('Error saving to localStorage:', error);
            return false;
        }
    },

    /**
     * Get from local storage with JSON deserialization
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return defaultValue;
        }
    },

    /**
     * Remove from local storage
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Error removing from localStorage:', error);
            return false;
        }
    },

    /**
     * Clear all local storage
     */
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Error clearing localStorage:', error);
            return false;
        }
    }
};

// Error Handling Utilities
const ErrorUtils = {
    /**
     * Extract error message from API response
     */
    getErrorMessage(error) {
        if (error.response?.data?.message) {
            return error.response.data.message;
        }

        if (error.response?.data?.error) {
            return error.response.data.error;
        }

        if (error.message) {
            return error.message;
        }

        return 'Ein unerwarteter Fehler ist aufgetreten.';
    },

    /**
     * Get user-friendly error message by status code
     */
    getStatusErrorMessage(statusCode) {
        const messages = {
            400: 'Ungültige Anfrage. Bitte überprüfen Sie Ihre Eingaben.',
            401: 'Nicht autorisiert. Bitte melden Sie sich an.',
            403: 'Zugriff verweigert.',
            404: 'Die angeforderte Ressource wurde nicht gefunden.',
            422: 'Die Eingabedaten konnten nicht verarbeitet werden.',
            500: 'Ein Serverfehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
            503: 'Der Service ist vorübergehend nicht verfügbar.'
        };

        return messages[statusCode] || 'Ein Fehler ist aufgetreten.';
    },

    /**
     * Check if error is network error
     */
    isNetworkError(error) {
        return !error.response && error.request;
    }
};

// UI Utilities
const UIUtils = {
    /**
     * Scroll to element smoothly
     */
    scrollTo(elementOrSelector) {
        const element = typeof elementOrSelector === 'string'
            ? document.querySelector(elementOrSelector)
            : elementOrSelector;

        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },

    /**
     * Toggle body scroll
     */
    toggleBodyScroll(enable = true) {
        document.body.style.overflow = enable ? '' : 'hidden';
    },

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (error) {
            console.error('Failed to copy to clipboard:', error);
            return false;
        }
    },

    /**
     * Debounce function
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Export utilities to global scope for Alpine.js access
window.CustomerPortalAPI = CustomerPortalAPI;
window.DateTimeUtils = DateTimeUtils;
window.ValidationUtils = ValidationUtils;
window.StatusUtils = StatusUtils;
window.StorageUtils = StorageUtils;
window.ErrorUtils = ErrorUtils;
window.UIUtils = UIUtils;
