/**
 * Bridge layer between React (Cal.com Atoms) and Livewire
 * Handles bidirectional communication via Alpine.js events
 */
export class CalcomBridge {
    /**
     * Emit event to Livewire
     */
    static emit(eventName, data) {
        window.dispatchEvent(new CustomEvent(`calcom:${eventName}`, {
            detail: data,
        }));

        // Also emit via Livewire if available
        if (window.Livewire) {
            window.Livewire.dispatch(`calcom:${eventName}`, data);
        }
    }

    /**
     * Listen for events from Livewire
     */
    static on(eventName, callback) {
        window.addEventListener(`livewire:${eventName}`, (e) => {
            callback(e.detail);
        });
    }

    /**
     * Fetch data from Laravel API
     */
    static async fetch(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin', // Include cookies for session auth
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
                ...options.headers,
            },
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Error Response:', errorText);
            throw new Error(`API Error: ${response.statusText}`);
        }

        return response.json();
    }
}
