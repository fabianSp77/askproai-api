import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import morph from '@alpinejs/morph';

// Import stores
import portalStore from './stores/portalStore';

// Import components
import dropdown from './components/alpine/dropdown';
import modal from './components/alpine/modal';
import tabs from './components/alpine/tabs';
import toast from './components/alpine/toast';
import datepicker from './components/alpine/datepicker';
import search from './components/alpine/search';
import sidebar from './components/alpine/sidebar';
import notifications from './components/alpine/notifications';
import branchSelector from './components/alpine/branchSelector';
import statsCard from './components/alpine/statsCard';
import dataTable from './components/alpine/dataTable';
import formValidation from './components/alpine/formValidation';

// Alpine plugins
Alpine.plugin(persist);
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);
Alpine.plugin(morph);

// Register global stores
Alpine.store('portal', portalStore());

// Register components
Alpine.data('dropdown', dropdown);
Alpine.data('modal', modal);
Alpine.data('tabs', tabs);
Alpine.data('toast', toast);
Alpine.data('datepicker', datepicker);
Alpine.data('search', search);
Alpine.data('sidebar', sidebar);
Alpine.data('notifications', notifications);
Alpine.data('branchSelector', branchSelector);
Alpine.data('statsCard', statsCard);
Alpine.data('dataTable', dataTable);
Alpine.data('formValidation', formValidation);

// Global Alpine utilities
Alpine.magic('formatDate', () => {
    return (date, format = 'DD.MM.YYYY') => {
        if (!date) return '';
        return window.dayjs(date).format(format);
    };
});

Alpine.magic('formatCurrency', () => {
    return (amount, currency = 'EUR') => {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency
        }).format(amount);
    };
});

Alpine.magic('formatDuration', () => {
    return (seconds) => {
        if (!seconds) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };
});

Alpine.magic('debounce', () => {
    return (callback, delay = 300) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => callback(...args), delay);
        };
    };
});

Alpine.magic('clipboard', () => {
    return {
        copy: async (text) => {
            try {
                await navigator.clipboard.writeText(text);
                Alpine.store('portal').showToast('Kopiert', 'Text in die Zwischenablage kopiert', 'success');
                return true;
            } catch (err) {
                Alpine.store('portal').showToast('Fehler', 'Konnte nicht kopieren', 'error');
                return false;
            }
        }
    };
});

// Initialize Alpine when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.Alpine = Alpine;
    Alpine.start();
});

// Export for use in other scripts
export default Alpine;