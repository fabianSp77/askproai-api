import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// IMPORTANT: Alpine.js and Sortable removed from here!
// They are now only loaded in admin area via bootstrap-admin.js
// This prevents conflicts on public pages like login
