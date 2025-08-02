import { describe, it, expect, beforeEach, vi } from 'vitest';
import Alpine from 'alpinejs';
import dropdown from '../../../resources/js/components/alpine/dropdown';
import modal from '../../../resources/js/components/alpine/modal';
import toast from '../../../resources/js/components/alpine/toast';
import tabs from '../../../resources/js/components/alpine/tabs';
import datepicker from '../../../resources/js/components/alpine/datepicker';

// Mock DOM
document.body.innerHTML = `
    <div id="test-container"></div>
`;

describe('Alpine Components', () => {
    let container;

    beforeEach(() => {
        container = document.getElementById('test-container');
        container.innerHTML = '';
        Alpine.plugin(() => {});
    });

    describe('Dropdown Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="dropdown()">
                    <button @click="toggle()" x-ref="button">Toggle</button>
                    <div x-show="open" x-ref="panel" @click.outside="close()">
                        <a href="#">Option 1</a>
                        <a href="#">Option 2</a>
                    </div>
                </div>
            `;
            Alpine.data('dropdown', dropdown);
            Alpine.start();
        });

        it('should toggle dropdown on button click', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const button = container.querySelector('button');
            
            expect(component.open).toBe(false);
            
            button.click();
            await Alpine.nextTick();
            expect(component.open).toBe(true);
            
            button.click();
            await Alpine.nextTick();
            expect(component.open).toBe(false);
        });

        it('should close dropdown on outside click', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const button = container.querySelector('button');
            
            button.click();
            await Alpine.nextTick();
            expect(component.open).toBe(true);
            
            document.body.click();
            await Alpine.nextTick();
            expect(component.open).toBe(false);
        });

        it('should close dropdown on escape key', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.open = true;
            await Alpine.nextTick();
            
            const event = new KeyboardEvent('keydown', { key: 'Escape' });
            window.dispatchEvent(event);
            await Alpine.nextTick();
            
            expect(component.open).toBe(false);
        });
    });

    describe('Modal Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="modal()">
                    <button @click="open()">Open Modal</button>
                    <div x-show="show" x-transition>
                        <div class="modal-backdrop" @click="close()"></div>
                        <div class="modal-content">
                            <h2>Modal Title</h2>
                            <button @click="close()">Close</button>
                        </div>
                    </div>
                </div>
            `;
            Alpine.data('modal', modal);
            Alpine.start();
        });

        it('should open and close modal', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const openButton = container.querySelector('button');
            
            expect(component.show).toBe(false);
            
            openButton.click();
            await Alpine.nextTick();
            expect(component.show).toBe(true);
            
            const closeButton = container.querySelectorAll('button')[1];
            closeButton.click();
            await Alpine.nextTick();
            expect(component.show).toBe(false);
        });

        it('should close modal on backdrop click', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.open();
            await Alpine.nextTick();
            
            const backdrop = container.querySelector('.modal-backdrop');
            backdrop.click();
            await Alpine.nextTick();
            
            expect(component.show).toBe(false);
        });

        it('should prevent body scroll when modal is open', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.open();
            await Alpine.nextTick();
            expect(document.body.style.overflow).toBe('hidden');
            
            component.close();
            await Alpine.nextTick();
            expect(document.body.style.overflow).toBe('');
        });
    });

    describe('Toast Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="toast()">
                    <div x-show="visible" x-transition>
                        <div class="toast" :class="type">
                            <span x-text="message"></span>
                            <button @click="dismiss()">×</button>
                        </div>
                    </div>
                </div>
            `;
            Alpine.data('toast', toast);
            Alpine.start();
        });

        it('should show toast with message', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.show('Test message', 'success');
            await Alpine.nextTick();
            
            expect(component.visible).toBe(true);
            expect(component.message).toBe('Test message');
            expect(component.type).toBe('success');
        });

        it('should auto-dismiss toast after timeout', async () => {
            vi.useFakeTimers();
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.show('Test message', 'info', 3000);
            await Alpine.nextTick();
            
            expect(component.visible).toBe(true);
            
            vi.advanceTimersByTime(3000);
            await vi.runAllTimersAsync();
            
            expect(component.visible).toBe(false);
            vi.useRealTimers();
        });

        it('should dismiss toast manually', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.show('Test message', 'error');
            await Alpine.nextTick();
            
            const dismissButton = container.querySelector('button');
            dismissButton.click();
            await Alpine.nextTick();
            
            expect(component.visible).toBe(false);
        });
    });

    describe('Tabs Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="tabs()">
                    <div class="tab-buttons">
                        <button @click="activeTab = 'tab1'" :class="{ active: activeTab === 'tab1' }">Tab 1</button>
                        <button @click="activeTab = 'tab2'" :class="{ active: activeTab === 'tab2' }">Tab 2</button>
                        <button @click="activeTab = 'tab3'" :class="{ active: activeTab === 'tab3' }">Tab 3</button>
                    </div>
                    <div class="tab-panels">
                        <div x-show="activeTab === 'tab1'">Content 1</div>
                        <div x-show="activeTab === 'tab2'">Content 2</div>
                        <div x-show="activeTab === 'tab3'">Content 3</div>
                    </div>
                </div>
            `;
            Alpine.data('tabs', tabs);
            Alpine.start();
        });

        it('should switch between tabs', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const buttons = container.querySelectorAll('button');
            
            expect(component.activeTab).toBe('tab1');
            
            buttons[1].click();
            await Alpine.nextTick();
            expect(component.activeTab).toBe('tab2');
            
            buttons[2].click();
            await Alpine.nextTick();
            expect(component.activeTab).toBe('tab3');
        });

        it('should update active tab styling', async () => {
            const buttons = container.querySelectorAll('button');
            
            expect(buttons[0].classList.contains('active')).toBe(true);
            expect(buttons[1].classList.contains('active')).toBe(false);
            
            buttons[1].click();
            await Alpine.nextTick();
            
            expect(buttons[0].classList.contains('active')).toBe(false);
            expect(buttons[1].classList.contains('active')).toBe(true);
        });

        it('should persist active tab in URL hash', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            
            component.switchTab('tab2');
            await Alpine.nextTick();
            
            expect(window.location.hash).toBe('#tab2');
        });

        it('should restore tab from URL hash on init', () => {
            window.location.hash = '#tab3';
            
            const newContainer = document.createElement('div');
            newContainer.innerHTML = `<div x-data="tabs()"></div>`;
            document.body.appendChild(newContainer);
            
            Alpine.start();
            const component = Alpine.$data(newContainer.querySelector('[x-data]'));
            
            expect(component.activeTab).toBe('tab3');
        });
    });

    describe('Datepicker Component', () => {
        beforeEach(() => {
            container.innerHTML = `
                <div x-data="datepicker()">
                    <input type="text" x-model="selectedDate" @click="open()" x-ref="input" />
                    <div x-show="isOpen" class="calendar" @click.outside="close()">
                        <div class="calendar-header">
                            <button @click="previousMonth()">‹</button>
                            <span x-text="monthYear"></span>
                            <button @click="nextMonth()">›</button>
                        </div>
                        <div class="calendar-grid">
                            <template x-for="day in days" :key="day.date">
                                <button 
                                    @click="selectDate(day.date)" 
                                    :class="{ selected: isSelected(day.date) }"
                                    x-text="day.number"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            `;
            Alpine.data('datepicker', datepicker);
            Alpine.start();
        });

        it('should open calendar on input click', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const input = container.querySelector('input');
            
            expect(component.isOpen).toBe(false);
            
            input.click();
            await Alpine.nextTick();
            
            expect(component.isOpen).toBe(true);
        });

        it('should select date and update input', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const input = container.querySelector('input');
            
            component.open();
            await Alpine.nextTick();
            
            const dateToSelect = new Date();
            dateToSelect.setDate(15);
            component.selectDate(dateToSelect);
            await Alpine.nextTick();
            
            expect(component.selectedDate).toBeDefined();
            expect(component.isOpen).toBe(false);
            expect(input.value).toContain('15');
        });

        it('should navigate between months', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const currentMonth = component.currentMonth;
            const currentYear = component.currentYear;
            
            component.nextMonth();
            await Alpine.nextTick();
            
            if (currentMonth === 11) {
                expect(component.currentMonth).toBe(0);
                expect(component.currentYear).toBe(currentYear + 1);
            } else {
                expect(component.currentMonth).toBe(currentMonth + 1);
                expect(component.currentYear).toBe(currentYear);
            }
            
            component.previousMonth();
            component.previousMonth();
            await Alpine.nextTick();
            
            if (currentMonth === 0) {
                expect(component.currentMonth).toBe(11);
                expect(component.currentYear).toBe(currentYear - 1);
            } else {
                expect(component.currentMonth).toBe(currentMonth - 1);
            }
        });

        it('should highlight selected date', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const today = new Date();
            
            component.selectedDate = today.toISOString().split('T')[0];
            expect(component.isSelected(today)).toBe(true);
            
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            expect(component.isSelected(tomorrow)).toBe(false);
        });

        it('should emit date-selected event', async () => {
            const component = Alpine.$data(container.querySelector('[x-data]'));
            const mockCallback = vi.fn();
            window.addEventListener('date-selected', mockCallback);
            
            const dateToSelect = new Date();
            component.selectDate(dateToSelect);
            await Alpine.nextTick();
            
            expect(mockCallback).toHaveBeenCalled();
            expect(mockCallback.mock.calls[0][0].detail.date).toEqual(dateToSelect);
        });
    });
});