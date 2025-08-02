export default () => ({
    open: false,
    value: null,
    displayValue: '',
    format: 'DD.MM.YYYY',
    minDate: null,
    maxDate: null,
    currentMonth: new Date(),
    
    init() {
        // Initialize with value from input
        if (this.$refs.input?.value) {
            this.value = this.$refs.input.value;
            this.displayValue = this.formatDate(this.value);
        }
        
        // Close on click outside
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    this.focusToday();
                });
            }
        });
    },
    
    toggle() {
        this.open = !this.open;
    },
    
    selectDate(date) {
        this.value = date;
        this.displayValue = this.formatDate(date);
        this.$refs.input.value = date;
        this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        this.open = false;
    },
    
    formatDate(date) {
        if (!date) return '';
        return window.dayjs(date).format(this.format);
    },
    
    get monthYear() {
        return window.dayjs(this.currentMonth).format('MMMM YYYY');
    },
    
    get daysInMonth() {
        const start = window.dayjs(this.currentMonth).startOf('month');
        const end = window.dayjs(this.currentMonth).endOf('month');
        const days = [];
        
        // Add empty cells for days before month start
        const startDay = start.day() || 7; // Convert Sunday (0) to 7
        for (let i = 1; i < startDay; i++) {
            days.push(null);
        }
        
        // Add days of month
        for (let date = start; date.isBefore(end) || date.isSame(end); date = date.add(1, 'day')) {
            days.push(date.format('YYYY-MM-DD'));
        }
        
        return days;
    },
    
    getDayNumber(date) {
        return date ? window.dayjs(date).format('D') : '';
    },
    
    isToday(date) {
        return date && window.dayjs(date).isSame(window.dayjs(), 'day');
    },
    
    isSelected(date) {
        return date && this.value && window.dayjs(date).isSame(this.value, 'day');
    },
    
    isDisabled(date) {
        if (!date) return true;
        const day = window.dayjs(date);
        
        if (this.minDate && day.isBefore(this.minDate, 'day')) return true;
        if (this.maxDate && day.isAfter(this.maxDate, 'day')) return true;
        
        return false;
    },
    
    previousMonth() {
        this.currentMonth = window.dayjs(this.currentMonth).subtract(1, 'month').toDate();
    },
    
    nextMonth() {
        this.currentMonth = window.dayjs(this.currentMonth).add(1, 'month').toDate();
    },
    
    focusToday() {
        const todayButton = this.$el.querySelector('[data-today="true"]:not([disabled])');
        if (todayButton) {
            todayButton.focus();
        } else {
            const firstEnabled = this.$el.querySelector('button[data-date]:not([disabled])');
            firstEnabled?.focus();
        }
    },
    
    handleKeydown(event) {
        if (!this.open) return;
        
        const focusedDate = document.activeElement.dataset.date;
        if (!focusedDate) return;
        
        let newDate;
        
        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).subtract(1, 'day');
                break;
                
            case 'ArrowRight':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).add(1, 'day');
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).subtract(1, 'week');
                break;
                
            case 'ArrowDown':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).add(1, 'week');
                break;
                
            case 'Home':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).startOf('month');
                break;
                
            case 'End':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).endOf('month');
                break;
                
            case 'PageUp':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).subtract(1, 'month');
                this.currentMonth = newDate.toDate();
                break;
                
            case 'PageDown':
                event.preventDefault();
                newDate = window.dayjs(focusedDate).add(1, 'month');
                this.currentMonth = newDate.toDate();
                break;
                
            case 'Enter':
            case ' ':
                event.preventDefault();
                this.selectDate(focusedDate);
                return;
                
            case 'Escape':
                event.preventDefault();
                this.open = false;
                this.$refs.input?.focus();
                return;
        }
        
        if (newDate) {
            // Update month if needed
            if (!newDate.isSame(this.currentMonth, 'month')) {
                this.currentMonth = newDate.toDate();
            }
            
            // Focus new date
            this.$nextTick(() => {
                const button = this.$el.querySelector(`[data-date="${newDate.format('YYYY-MM-DD')}"]`);
                button?.focus();
            });
        }
    }
});