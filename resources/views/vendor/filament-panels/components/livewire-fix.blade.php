{{-- Livewire override removed - unified-ui-fix.js handles all fixes --}}

<script>
// Additional inline overrides
(function() {
    // Immediately override document.write
    const _write = document.write;
    const _writeln = document.writeln;
    
    document.write = function(content) {
        if (content && content.toString().match(/419|Page Expired|page expired/i)) {
            console.log('Blocked document.write with error content');
            return;
        }
        return _write.call(document, content);
    };
    
    document.writeln = function(content) {
        if (content && content.toString().match(/419|Page Expired|page expired/i)) {
            console.log('Blocked document.writeln with error content');
            return;
        }
        return _writeln.call(document, content);
    };
    
    // Override window.open to prevent error popups
    const _open = window.open;
    window.open = function(url, name, features) {
        if (url && url.includes('419')) {
            console.log('Blocked window.open with 419');
            return null;
        }
        return _open.call(window, url, name, features);
    };
})();
</script>