import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Import cookie consent manager
import './cookie-consent';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.start();
