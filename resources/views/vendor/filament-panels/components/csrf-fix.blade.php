{{-- CSRF Fix - Minimal Version --}}
<script>
(function() {
    console.log("CSRF Fix - Minimal Version Active");
    
    // Only handle Livewire CSRF tokens
    if (window.Livewire) {
        Livewire.hook("request", ({ options }) => {
            options.headers = options.headers || {};
            options.headers["X-CSRF-TOKEN"] = document.querySelector('meta[name="csrf-token"]')?.content || "";
            options.headers["X-Requested-With"] = "XMLHttpRequest";
        });
    }
})();
</script>