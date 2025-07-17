<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold mb-2">Payment Link Details</h3>
        <p class="text-gray-600 dark:text-gray-400">
            Company: <strong>{{ $company->name }}</strong>
        </p>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Payment Link URL
        </label>
        <div class="flex items-center space-x-2">
            <input type="text" 
                   value="{{ $url }}" 
                   readonly 
                   class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                   id="payment-link-url">
            <button type="button" 
                    onclick="copyToClipboard('payment-link-url')"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-heroicon-o-clipboard-copy class="w-4 h-4 mr-1" />
                Kopieren
            </button>
        </div>
    </div>
    
    <div class="text-center">
        <img src="{{ $qrUrl }}" alt="QR Code" class="mx-auto mb-2">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            QR-Code für Payment Link
        </p>
    </div>
    
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
        <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Verwendung</h4>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>• Link per E-Mail oder WhatsApp versenden</li>
            <li>• QR-Code ausdrucken und im Geschäft aufhängen</li>
            <li>• Link auf der Website einbetten</li>
            <li>• Link ist dauerhaft gültig und wiederverwendbar</li>
        </ul>
    </div>
    
    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
        <h4 class="font-medium text-yellow-900 dark:text-yellow-100 mb-2">Test-Kreditkarten</h4>
        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
            <li>• Erfolg: 4242 4242 4242 4242</li>
            <li>• 3D Secure: 4000 0025 0000 3155</li>
            <li>• Ablehnung: 4000 0000 0000 0002</li>
        </ul>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show notification
    window.$wireui.notify({
        title: 'Kopiert!',
        description: 'Payment Link wurde in die Zwischenablage kopiert.',
        icon: 'success'
    });
}
</script>