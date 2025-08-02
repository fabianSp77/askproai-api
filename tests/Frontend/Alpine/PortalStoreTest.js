import { describe, it, expect, beforeEach, vi } from 'vitest';
import Alpine from 'alpinejs';
import portalStore from '../../../resources/js/stores/portalStore';

// Mock Echo
const mockEcho = {
    connector: {
        pusher: {
            connection: { state: 'connected' }
        }
    },
    private: vi.fn().mockReturnThis(),
    listen: vi.fn().mockReturnThis(),
    error: vi.fn().mockReturnThis(),
    leave: vi.fn().mockReturnThis()
};

// Mock window.Echo
global.Echo = mockEcho;

// Mock fetch
global.fetch = vi.fn();

describe('Portal Store', () => {
    let store;

    beforeEach(() => {
        // Reset mocks
        vi.clearAllMocks();
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ data: {} })
        });
        
        // Initialize store
        Alpine.store('portal', portalStore());
        store = Alpine.store('portal');
    });

    describe('Initialization', () => {
        it('should initialize with default values', () => {
            expect(store.user).toBe(null);
            expect(store.company).toBe(null);
            expect(store.branches).toEqual([]);
            expect(store.currentBranch).toBe(null);
            expect(store.notifications).toEqual([]);
            expect(store.loading).toBe(false);
            expect(store.connected).toBe(false);
        });

        it('should initialize WebSocket connection', async () => {
            await store.init();
            
            expect(store.echo).toBeDefined();
            expect(store.connected).toBe(true);
            expect(mockEcho.private).toHaveBeenCalledWith('company.1');
        });
    });

    describe('Authentication', () => {
        it('should load user data successfully', async () => {
            const userData = {
                id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                company: {
                    id: 1,
                    name: 'Test Company'
                },
                branches: [
                    { id: 1, name: 'Main Branch' },
                    { id: 2, name: 'Second Branch' }
                ]
            };

            fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: userData })
            });

            await store.loadUser();

            expect(store.user).toEqual({
                id: 1,
                name: 'John Doe',
                email: 'john@example.com'
            });
            expect(store.company).toEqual({
                id: 1,
                name: 'Test Company'
            });
            expect(store.branches).toHaveLength(2);
            expect(store.currentBranch).toEqual({ id: 1, name: 'Main Branch' });
        });

        it('should handle logout', async () => {
            store.user = { id: 1, name: 'John Doe' };
            store.company = { id: 1, name: 'Test Company' };
            
            fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true })
            });

            await store.logout();

            expect(store.user).toBe(null);
            expect(store.company).toBe(null);
            expect(store.branches).toEqual([]);
            expect(store.currentBranch).toBe(null);
            expect(mockEcho.leave).toHaveBeenCalled();
        });
    });

    describe('Notifications', () => {
        it('should add notification', () => {
            store.notify('Test message', 'success');
            
            expect(store.notifications).toHaveLength(1);
            expect(store.notifications[0]).toMatchObject({
                message: 'Test message',
                type: 'success'
            });
        });

        it('should remove notification after timeout', async () => {
            vi.useFakeTimers();
            
            store.notify('Test message', 'info');
            expect(store.notifications).toHaveLength(1);
            
            vi.advanceTimersByTime(5000);
            await vi.runAllTimersAsync();
            
            expect(store.notifications).toHaveLength(0);
            
            vi.useRealTimers();
        });

        it('should dismiss notification manually', () => {
            store.notify('Test message 1', 'success');
            store.notify('Test message 2', 'error');
            
            const notificationId = store.notifications[0].id;
            store.dismissNotification(notificationId);
            
            expect(store.notifications).toHaveLength(1);
            expect(store.notifications[0].message).toBe('Test message 2');
        });
    });

    describe('Branch Management', () => {
        beforeEach(() => {
            store.branches = [
                { id: 1, name: 'Main Branch' },
                { id: 2, name: 'Second Branch' }
            ];
            store.currentBranch = store.branches[0];
        });

        it('should switch branch', () => {
            store.switchBranch(2);
            
            expect(store.currentBranch).toEqual({ id: 2, name: 'Second Branch' });
            expect(localStorage.getItem('selectedBranchId')).toBe('2');
        });

        it('should emit branch changed event', () => {
            const mockCallback = vi.fn();
            window.addEventListener('branch-changed', mockCallback);
            
            store.switchBranch(2);
            
            expect(mockCallback).toHaveBeenCalled();
            expect(mockCallback.mock.calls[0][0].detail).toEqual({
                branch: { id: 2, name: 'Second Branch' }
            });
        });
    });

    describe('API Methods', () => {
        it('should make authenticated API calls', async () => {
            const testData = { test: 'data' };
            fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: testData })
            });

            const result = await store.api('/test-endpoint', {
                method: 'GET'
            });

            expect(fetch).toHaveBeenCalledWith('/api/test-endpoint', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            expect(result).toEqual(testData);
        });

        it('should handle API errors', async () => {
            fetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
                json: async () => ({ message: 'Not found' })
            });

            await expect(store.api('/test-endpoint')).rejects.toThrow('Not found');
        });

        it('should show loading state during API calls', async () => {
            let resolvePromise;
            const promise = new Promise((resolve) => {
                resolvePromise = resolve;
            });
            
            fetch.mockReturnValueOnce(promise);
            
            const apiCall = store.api('/test-endpoint');
            expect(store.loading).toBe(true);
            
            resolvePromise({
                ok: true,
                json: async () => ({ data: {} })
            });
            
            await apiCall;
            expect(store.loading).toBe(false);
        });
    });

    describe('WebSocket Events', () => {
        beforeEach(async () => {
            await store.init();
        });

        it('should handle notification events', () => {
            const notificationHandler = mockEcho.listen.mock.calls.find(
                call => call[0] === '.notification.sent'
            )?.[1];

            expect(notificationHandler).toBeDefined();

            notificationHandler({
                message: 'New appointment booked',
                type: 'success'
            });

            expect(store.notifications).toHaveLength(1);
            expect(store.notifications[0].message).toBe('New appointment booked');
        });

        it('should handle data update events', () => {
            const updateHandler = mockEcho.listen.mock.calls.find(
                call => call[0] === '.data.updated'
            )?.[1];

            expect(updateHandler).toBeDefined();

            const mockCallback = vi.fn();
            window.addEventListener('portal-data-updated', mockCallback);

            updateHandler({
                entity: 'appointment',
                action: 'created',
                data: { id: 1, status: 'scheduled' }
            });

            expect(mockCallback).toHaveBeenCalled();
            expect(mockCallback.mock.calls[0][0].detail).toEqual({
                entity: 'appointment',
                action: 'created',
                data: { id: 1, status: 'scheduled' }
            });
        });
    });

    describe('State Persistence', () => {
        it('should persist selected branch', () => {
            store.branches = [
                { id: 1, name: 'Main Branch' },
                { id: 2, name: 'Second Branch' }
            ];
            
            store.switchBranch(2);
            
            // Simulate page reload
            const newStore = portalStore();
            newStore.branches = store.branches;
            
            expect(newStore.currentBranch).toEqual({ id: 2, name: 'Second Branch' });
        });

        it('should fallback to first branch if persisted branch not found', () => {
            localStorage.setItem('selectedBranchId', '999');
            
            const newStore = portalStore();
            newStore.branches = [
                { id: 1, name: 'Main Branch' },
                { id: 2, name: 'Second Branch' }
            ];
            
            expect(newStore.currentBranch).toEqual({ id: 1, name: 'Main Branch' });
        });
    });

    describe('Error Handling', () => {
        it('should handle WebSocket connection errors', async () => {
            mockEcho.connector.pusher.connection.state = 'disconnected';
            
            await store.init();
            
            expect(store.connected).toBe(false);
            expect(store.notifications).toHaveLength(1);
            expect(store.notifications[0].type).toBe('error');
        });

        it('should retry failed API calls', async () => {
            fetch
                .mockRejectedValueOnce(new Error('Network error'))
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: { success: true } })
                });

            const result = await store.api('/test-endpoint', {
                retry: true
            });

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(result).toEqual({ success: true });
        });
    });
});