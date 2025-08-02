export default () => ({
    init() {
        // Component is controlled by portal store
        this.portal = Alpine.store('portal');
    },
    
    get notifications() {
        return this.portal.notifications;
    },
    
    get unreadCount() {
        return this.portal.unreadNotifications;
    },
    
    get isOpen() {
        return this.portal.notificationsOpen;
    },
    
    toggle() {
        this.portal.toggleNotifications();
    },
    
    async markAsRead(notificationId) {
        await this.portal.markNotificationAsRead(notificationId);
    },
    
    async markAllAsRead() {
        await this.portal.markAllNotificationsAsRead();
    },
    
    getIcon(type) {
        const icons = {
            'appointment.created': '📅',
            'appointment.confirmed': '✅',
            'appointment.cancelled': '❌',
            'appointment.reminder': '⏰',
            'call.received': '📞',
            'call.missed': '📵',
            'invoice.created': '💰',
            'invoice.paid': '💵',
            'team.member_added': '👥',
            'system.update': 'ℹ️',
            'system.alert': '⚠️',
            'feedback.received': '💬'
        };
        return icons[type] || '📌';
    },
    
    getTimeAgo(date) {
        const now = new Date();
        const notificationDate = new Date(date);
        const seconds = Math.floor((now - notificationDate) / 1000);
        
        if (seconds < 60) return 'Gerade eben';
        if (seconds < 3600) return `Vor ${Math.floor(seconds / 60)} Minuten`;
        if (seconds < 86400) return `Vor ${Math.floor(seconds / 3600)} Stunden`;
        if (seconds < 604800) return `Vor ${Math.floor(seconds / 86400)} Tagen`;
        
        return notificationDate.toLocaleDateString('de-DE');
    },
    
    handleAction(notification) {
        if (notification.action_url) {
            window.location.href = notification.action_url;
        }
        this.markAsRead(notification.id);
    }
});