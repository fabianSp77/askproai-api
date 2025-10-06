import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Auto-connect to user's private channels if authenticated
if (window.Laravel && window.Laravel.user) {
    // Listen to user's personal channel
    window.Echo.private(`user.${window.Laravel.user.id}`)
        .listen('.appointment.reminder', (e) => {
            console.log('Appointment reminder:', e);
        })
        .listen('.appointment.confirmed', (e) => {
            console.log('Appointment confirmed:', e);
        });

    // Connect to branch channel if user has branch
    if (window.Laravel.user.branch_id) {
        window.Echo.private(`branch.${window.Laravel.user.branch_id}`)
            .listen('.appointment.created', (e) => {
                console.log('New appointment in branch:', e);
            })
            .listen('.appointment.updated', (e) => {
                console.log('Appointment updated in branch:', e);
            })
            .listen('.appointment.deleted', (e) => {
                console.log('Appointment deleted in branch:', e);
            });
    }

    // Connect to staff channel if user is staff
    if (window.Laravel.user.staff_id) {
        window.Echo.private(`staff.${window.Laravel.user.staff_id}`)
            .listen('.appointment.assigned', (e) => {
                console.log('New appointment assigned:', e);
            })
            .listen('.appointment.updated', (e) => {
                console.log('Your appointment updated:', e);
            });
    }
}

// Global appointment channel for all updates
window.Echo.channel('appointments')
    .listen('.appointment.created', (e) => {
        if (window.appointmentCalendar) {
            window.appointmentCalendar.refetchEvents();
        }
        // Trigger Livewire event
        if (window.Livewire) {
            window.Livewire.dispatch('refreshCalendar');
        }
    })
    .listen('.appointment.updated', (e) => {
        if (window.appointmentCalendar) {
            window.appointmentCalendar.refetchEvents();
        }
        if (window.Livewire) {
            window.Livewire.dispatch('refreshCalendar');
        }
    })
    .listen('.appointment.deleted', (e) => {
        if (window.appointmentCalendar) {
            window.appointmentCalendar.refetchEvents();
        }
        if (window.Livewire) {
            window.Livewire.dispatch('refreshCalendar');
        }
    });

export default window.Echo;