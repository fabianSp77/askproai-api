@props([
    'slots' => [],
    'selectedSlot' => null
])

<div x-data="timeSlotPicker" class="space-y-4">
    <!-- Date Navigation -->
    <div class="flex items-center justify-between bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <button @click="previousWeek"
                class="p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                :disabled="currentWeekIndex === 0"
                :class="{ 'opacity-50 cursor-not-allowed': currentWeekIndex === 0 }">
            <i class="fas fa-chevron-left text-gray-600"></i>
        </button>

        <div class="text-center">
            <p class="text-sm text-gray-500">Woche</p>
            <p class="text-lg font-semibold text-gray-900" x-text="currentWeekLabel"></p>
        </div>

        <button @click="nextWeek"
                class="p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                :disabled="currentWeekIndex >= availableWeeks.length - 1"
                :class="{ 'opacity-50 cursor-not-allowed': currentWeekIndex >= availableWeeks.length - 1 }">
            <i class="fas fa-chevron-right text-gray-600"></i>
        </button>
    </div>

    <!-- Available Slots Grid -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <!-- Loading State -->
        <div x-show="loading" class="text-center py-8">
            @include('customer-portal.components.loading-spinner')
            <p class="mt-4 text-gray-600">Verfügbare Zeiten werden geladen...</p>
        </div>

        <!-- No Slots Available -->
        <div x-show="!loading && currentWeekSlots.length === 0" class="text-center py-8">
            <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-600">Keine verfügbaren Zeiten in dieser Woche.</p>
            <p class="text-sm text-gray-500 mt-2">Bitte wählen Sie eine andere Woche.</p>
        </div>

        <!-- Slots by Day -->
        <div x-show="!loading && currentWeekSlots.length > 0" class="space-y-4">
            <template x-for="(daySlots, dayName) in groupedSlots" :key="dayName">
                <div class="border-b border-gray-100 last:border-b-0 pb-4 last:pb-0">
                    <h4 class="text-sm font-medium text-gray-700 mb-3" x-text="dayName"></h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        <template x-for="slot in daySlots" :key="slot.id">
                            <button @click="selectSlot(slot)"
                                    type="button"
                                    class="px-4 py-3 border rounded-lg text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                    :class="{
                                        'border-primary bg-primary text-white shadow-sm': selectedSlotId === slot.id,
                                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:border-primary': selectedSlotId !== slot.id,
                                        'opacity-50 cursor-not-allowed': !slot.available
                                    }"
                                    :disabled="!slot.available">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-clock text-xs mb-1"></i>
                                    <span x-text="slot.time"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Selected Slot Confirmation -->
    <div x-show="selectedSlotData"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="bg-primary-50 border border-primary rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-primary text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h4 class="text-sm font-medium text-primary">Ausgewählter Termin</h4>
                <div class="mt-2 text-sm text-gray-700">
                    <p class="font-medium" x-text="selectedSlotData ? formatSlotDate(selectedSlotData) : ''"></p>
                    <p x-text="selectedSlotData ? `${selectedSlotData.time} Uhr` : ''"></p>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('timeSlotPicker', () => ({
            loading: false,
            slots: [],
            selectedSlotId: null,
            selectedSlotData: null,
            currentWeekIndex: 0,
            availableWeeks: [],

            init() {
                this.loadSlots();
            },

            get currentWeekLabel() {
                if (this.availableWeeks.length === 0) return '';
                const week = this.availableWeeks[this.currentWeekIndex];
                return week ? week.label : '';
            },

            get currentWeekSlots() {
                if (this.availableWeeks.length === 0) return [];
                const week = this.availableWeeks[this.currentWeekIndex];
                return week ? week.slots : [];
            },

            get groupedSlots() {
                const grouped = {};
                this.currentWeekSlots.forEach(slot => {
                    const dayName = this.formatDayName(slot.date);
                    if (!grouped[dayName]) {
                        grouped[dayName] = [];
                    }
                    grouped[dayName].push(slot);
                });
                return grouped;
            },

            async loadSlots() {
                this.loading = true;
                try {
                    // This would be populated from parent component
                    // For now, we'll use Alpine's $watch or events
                    this.$watch('$root.alternativeSlots', (value) => {
                        if (value && value.length > 0) {
                            this.processSlots(value);
                        }
                    });
                } catch (error) {
                    console.error('Error loading slots:', error);
                } finally {
                    this.loading = false;
                }
            },

            processSlots(slotsData) {
                // Group slots by week
                const weekMap = {};

                slotsData.forEach(slot => {
                    const weekKey = this.getWeekKey(slot.date);
                    if (!weekMap[weekKey]) {
                        weekMap[weekKey] = {
                            key: weekKey,
                            label: this.getWeekLabel(slot.date),
                            slots: []
                        };
                    }
                    weekMap[weekKey].slots.push(slot);
                });

                this.availableWeeks = Object.values(weekMap).sort((a, b) => a.key.localeCompare(b.key));
                this.loading = false;
            },

            getWeekKey(dateString) {
                const date = new Date(dateString);
                const week = this.getWeekNumber(date);
                const year = date.getFullYear();
                return `${year}-W${week.toString().padStart(2, '0')}`;
            },

            getWeekLabel(dateString) {
                const date = new Date(dateString);
                const week = this.getWeekNumber(date);
                const startOfWeek = this.getStartOfWeek(date);
                const endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(endOfWeek.getDate() + 6);

                return `KW ${week} (${startOfWeek.getDate()}.${startOfWeek.getMonth() + 1}. - ${endOfWeek.getDate()}.${endOfWeek.getMonth() + 1}.)`;
            },

            getWeekNumber(date) {
                const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
                const dayNum = d.getUTCDay() || 7;
                d.setUTCDate(d.getUTCDate() + 4 - dayNum);
                const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            },

            getStartOfWeek(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(d.setDate(diff));
            },

            formatDayName(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('de-DE', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                });
            },

            formatSlotDate(slot) {
                if (!slot || !slot.date) return '';
                const date = new Date(slot.date);
                return date.toLocaleDateString('de-DE', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            },

            selectSlot(slot) {
                if (!slot.available) return;

                this.selectedSlotId = slot.id;
                this.selectedSlotData = slot;

                // Emit event for parent component
                this.$dispatch('slot-selected', slot);
            },

            previousWeek() {
                if (this.currentWeekIndex > 0) {
                    this.currentWeekIndex--;
                }
            },

            nextWeek() {
                if (this.currentWeekIndex < this.availableWeeks.length - 1) {
                    this.currentWeekIndex++;
                }
            }
        }));
    });
</script>
@endpush
@endonce
