// Admin-specific bootstrap (with Alpine.js and Sortable)
import axios from 'axios';
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Make Alpine and Sortable available globally for admin area
window.Alpine = Alpine;
window.Sortable = Sortable;

// Don't start Alpine yet - let components register first