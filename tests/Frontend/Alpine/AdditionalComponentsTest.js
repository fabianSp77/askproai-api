import { describe, it, expect, beforeEach, vi } from 'vitest';
import Alpine from 'alpinejs';
import sidebar from '../../../resources/js/components/alpine/sidebar';
import notifications from '../../../resources/js/components/alpine/notifications';
import branchSelector from '../../../resources/js/components/alpine/branchSelector';
import statsCard from '../../../resources/js/components/alpine/statsCard';
import search from '../../../resources/js/components/alpine/search';

// Mock DOM
document.body.innerHTML = `
    <div id="test-container"></div>
`;

// Mock Alpine store
const mockStore = {
    portal: {
        branches: [
            { id: 1, name: 'Main Branch' },
            { id: 2, name: 'Second Branch' }
        ],
        currentBranch: { id: 1, name: 'Main Branch' },
        switchBranch: vi.fn(),
        notifications: [],
        dismissNotification: vi.fn()
    }
};

Alpine.store = vi.fn((name) => mockStore[name]);

describe('Additional Alpine Components', () => {
    let container;

    beforeEach(() => {
        container = document.getElementById('test-container');
        container.innerHTML = '';
        vi.clearAllMocks();
    });

    describe('Sidebar Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="sidebar()">
                    <button @click="toggle()" x-ref="toggleButton">Toggle</button>
                    <aside x-show="open" x-transition :class="{ 'collapsed': collapsed }">
                        <nav>
                            <a href="/dashboard" :class="{ 'active': isActive('/dashboard') }">Dashboard</a>
                            <a href="/appointments" :class="{ 'active': isActive('/appointments') }">Appointments</a>
                            <a href="/customers" :class="{ 'active': isActive('/customers') }">Customers</a>
                        </nav>
                        <button @click="collapse()">Collapse</button>
                    </aside>
                </div>
            `;
            Alpine.data('sidebar', sidebar);
            Alpine.start();
        });

        it('should toggle sidebar visibility', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const toggleButton = container.querySelector('button');
            
            expect(component.open).toBe(true); // Default open on desktop
            
            toggleButton.click();
            await Alpine.nextTick();
            expect(component.open).toBe(false);
            
            toggleButton.click();
            await Alpine.nextTick();
            expect(component.open).toBe(true);
        });

        it('should collapse/expand sidebar', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const collapseButton = container.querySelectorAll('button')[1];
            
            expect(component.collapsed).toBe(false);
            
            collapseButton.click();
            await Alpine.nextTick();
            expect(component.collapsed).toBe(true);
            
            collapseButton.click();
            await Alpine.nextTick();
            expect(component.collapsed).toBe(false);
        });

        it('should detect active route', () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            // Mock window.location.pathname
            Object.defineProperty(window, 'location', {
                value: { pathname: '/appointments' },
                writable: true
            });
            
            expect(component.isActive('/appointments')).toBe(true);
            expect(component.isActive('/dashboard')).toBe(false);
            expect(component.isActive('/customers')).toBe(false);
        });

        it('should persist sidebar state', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.collapse();
            await Alpine.nextTick();
            
            expect(localStorage.getItem('sidebar_collapsed')).toBe('true');
            
            // Simulate page reload
            const newComponent = sidebar();
            expect(newComponent.collapsed).toBe(true);
        });

        it('should handle mobile responsiveness', () => {
            // Mock mobile viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 500
            });
            
            const component = sidebar();
            expect(component.open).toBe(false); // Closed by default on mobile
            expect(component.isMobile()).toBe(true);
        });
    });

    describe('Notifications Component', () => {
        beforeEach(() => {
            mockStore.portal.notifications = [
                { id: 1, message: 'Success notification', type: 'success' },
                { id: 2, message: 'Error notification', type: 'error' }
            ];
            
            container.innerHTML = `
                <div x-data="notifications()">
                    <div class="notifications-container">
                        <template x-for="notification in notifications" :key="notification.id">
                            <div class="notification" :class="notification.type" x-transition>
                                <span x-text="notification.message"></span>
                                <button @click="dismiss(notification.id)">×</button>
                            </div>
                        </template>
                    </div>
                </div>
            `;
            Alpine.data('notifications', notifications);
            Alpine.start();
        });

        it('should display notifications from store', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            expect(component.notifications).toHaveLength(2);
            expect(component.notifications[0].message).toBe('Success notification');
            expect(component.notifications[1].message).toBe('Error notification');
        });

        it('should dismiss notification', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const dismissButtons = container.querySelectorAll('button');
            
            dismissButtons[0].click();
            await Alpine.nextTick();
            
            expect(mockStore.portal.dismissNotification).toHaveBeenCalledWith(1);
        });

        it('should handle notification animations', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            // Add new notification
            mockStore.portal.notifications.push({
                id: 3,
                message: 'New notification',
                type: 'info'
            });
            
            await Alpine.nextTick();
            expect(component.notifications).toHaveLength(3);
        });

        it('should apply notification styling based on type', () => {
            const successNotification = container.querySelector('.notification.success');
            const errorNotification = container.querySelector('.notification.error');
            
            expect(successNotification).toBeTruthy();
            expect(errorNotification).toBeTruthy();
        });
    });

    describe('Branch Selector Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="branchSelector()">
                    <button @click="open = !open" x-ref="button">
                        <span x-text="currentBranch?.name || 'Select Branch'"></span>
                    </button>
                    <div x-show="open" @click.outside="open = false">
                        <template x-for="branch in branches" :key="branch.id">
                            <button 
                                @click="selectBranch(branch.id)"
                                :class="{ 'active': branch.id === currentBranch?.id }"
                                x-text="branch.name"
                            ></button>
                        </template>
                    </div>
                </div>
            `;
            Alpine.data('branchSelector', branchSelector);
            Alpine.start();
        });

        it('should display current branch', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            expect(component.currentBranch.name).toBe('Main Branch');
            expect(container.querySelector('span').textContent).toBe('Main Branch');
        });

        it('should display available branches', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const toggleButton = container.querySelector('button');
            
            toggleButton.click();
            await Alpine.nextTick();
            
            expect(component.open).toBe(true);
            expect(component.branches).toHaveLength(2);
            
            const branchButtons = container.querySelectorAll('button');
            expect(branchButtons).toHaveLength(3); // Toggle + 2 branches
        });

        it('should switch branch', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const toggleButton = container.querySelector('button');
            
            toggleButton.click();
            await Alpine.nextTick();
            
            const branchButtons = container.querySelectorAll('button');
            branchButtons[2].click(); // Second branch
            await Alpine.nextTick();
            
            expect(mockStore.portal.switchBranch).toHaveBeenCalledWith(2);
            expect(component.open).toBe(false);
        });

        it('should highlight active branch', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const toggleButton = container.querySelector('button');
            
            toggleButton.click();
            await Alpine.nextTick();
            
            const firstBranchButton = container.querySelectorAll('button')[1];
            expect(firstBranchButton.classList.contains('active')).toBe(true);
        });
    });

    describe('Stats Card Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="statsCard({ 
                    title: 'Total Revenue', 
                    value: 15000, 
                    format: 'currency',
                    trend: { value: 15.5, direction: 'up' },
                    sparkline: [100, 150, 120, 180, 200]
                })">
                    <h3 x-text="title"></h3>
                    <div class="value" x-text="formattedValue"></div>
                    <div class="trend" :class="trend.direction">
                        <span x-text="trendText"></span>
                    </div>
                    <div x-ref="sparkline" class="sparkline"></div>
                </div>
            `;
            Alpine.data('statsCard', statsCard);
            Alpine.start();
        });

        it('should display formatted value', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            expect(component.title).toBe('Total Revenue');
            expect(component.formattedValue).toBe('$15,000.00');
        });

        it('should display trend information', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            expect(component.trendText).toBe('+15.5%');
            expect(container.querySelector('.trend').classList.contains('up')).toBe(true);
        });

        it('should handle different formats', () => {
            // Number format
            const numberCard = statsCard({ value: 1234, format: 'number' });
            expect(numberCard.formattedValue).toBe('1,234');
            
            // Percentage format
            const percentCard = statsCard({ value: 0.855, format: 'percentage' });
            expect(percentCard.formattedValue).toBe('85.5%');
            
            // Custom format
            const customCard = statsCard({ 
                value: 45, 
                format: 'custom',
                customFormat: (v) => `${v} items`
            });
            expect(customCard.formattedValue).toBe('45 items');
        });

        it('should animate value changes', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.updateValue(20000);
            
            // Should start animation
            expect(component.animating).toBe(true);
            
            // Wait for animation to complete
            await new Promise(resolve => setTimeout(resolve, 1100));
            
            expect(component.value).toBe(20000);
            expect(component.animating).toBe(false);
        });

        it('should render sparkline', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            // In real implementation, this would create SVG
            expect(component.sparkline).toEqual([100, 150, 120, 180, 200]);
            expect(component.hasSparkline).toBe(true);
        });
    });

    describe('Search Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="search()">
                    <input 
                        type="text" 
                        x-model="query" 
                        @input="search()"
                        placeholder="Search..."
                    />
                    <div x-show="showResults" @click.outside="closeResults()">
                        <div x-show="loading">Searching...</div>
                        <div x-show="!loading && results.length === 0">No results found</div>
                        <template x-for="result in results" :key="result.id">
                            <a 
                                :href="result.url" 
                                @click="selectResult(result)"
                                x-text="result.title"
                            ></a>
                        </template>
                    </div>
                </div>
            `;
            Alpine.data('search', search);
            Alpine.start();
        });

        it('should perform search on input', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const input = container.querySelector('input');
            
            // Mock search function
            component.performSearch = vi.fn().mockResolvedValue([
                { id: 1, title: 'Result 1', url: '/result1' },
                { id: 2, title: 'Result 2', url: '/result2' }
            ]);
            
            input.value = 'test';
            input.dispatchEvent(new Event('input'));
            
            await Alpine.nextTick();
            expect(component.query).toBe('test');
            expect(component.loading).toBe(true);
            
            // Wait for debounce
            await new Promise(resolve => setTimeout(resolve, 350));
            
            expect(component.performSearch).toHaveBeenCalledWith('test');
        });

        it('should display search results', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.results = [
                { id: 1, title: 'Appointment 1', url: '/appointments/1' },
                { id: 2, title: 'Customer John', url: '/customers/123' }
            ];
            component.showResults = true;
            
            await Alpine.nextTick();
            
            const links = container.querySelectorAll('a');
            expect(links).toHaveLength(2);
            expect(links[0].textContent).toBe('Appointment 1');
            expect(links[1].textContent).toBe('Customer John');
        });

        it('should handle empty results', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.query = 'nonexistent';
            component.results = [];
            component.showResults = true;
            component.loading = false;
            
            await Alpine.nextTick();
            
            expect(container.textContent).toContain('No results found');
        });

        it('should close results on outside click', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.showResults = true;
            await Alpine.nextTick();
            
            // Simulate outside click
            document.body.click();
            await Alpine.nextTick();
            
            expect(component.showResults).toBe(false);
        });

        it('should handle keyboard navigation', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const input = container.querySelector('input');
            
            component.results = [
                { id: 1, title: 'Result 1', url: '/result1' },
                { id: 2, title: 'Result 2', url: '/result2' }
            ];
            component.showResults = true;
            
            // Arrow down
            const downEvent = new KeyboardEvent('keydown', { key: 'ArrowDown' });
            input.dispatchEvent(downEvent);
            await Alpine.nextTick();
            
            expect(component.selectedIndex).toBe(0);
            
            // Arrow down again
            input.dispatchEvent(downEvent);
            await Alpine.nextTick();
            
            expect(component.selectedIndex).toBe(1);
            
            // Enter to select
            const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
            input.dispatchEvent(enterEvent);
            
            expect(component.selectResult).toBeDefined();
        });

        it('should debounce search requests', async () => {
            vi.useFakeTimers();
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const input = container.querySelector('input');
            
            component.performSearch = vi.fn();
            
            // Rapid typing
            input.value = 't';
            input.dispatchEvent(new Event('input'));
            input.value = 'te';
            input.dispatchEvent(new Event('input'));
            input.value = 'tes';
            input.dispatchEvent(new Event('input'));
            input.value = 'test';
            input.dispatchEvent(new Event('input'));
            
            // Should not call search yet
            expect(component.performSearch).not.toHaveBeenCalled();
            
            // Fast forward past debounce
            vi.advanceTimersByTime(350);
            
            // Should call search only once with final value
            expect(component.performSearch).toHaveBeenCalledTimes(1);
            expect(component.performSearch).toHaveBeenCalledWith('test');
            
            vi.useRealTimers();
        });
    });
});