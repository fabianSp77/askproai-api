// Minimal service worker to avoid 404 errors
self.addEventListener('fetch', function(event) {
  // Don't handle any requests, just prevent errors
});