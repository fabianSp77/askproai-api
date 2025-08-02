export default () => ({
    value: 0,
    previousValue: 0,
    loading: false,
    animated: true,
    displayValue: 0,
    
    init() {
        if (this.animated) {
            this.animateValue();
        } else {
            this.displayValue = this.value;
        }
    },
    
    animateValue() {
        const startValue = 0;
        const endValue = parseInt(this.value) || 0;
        const duration = 1000; // 1 second
        const steps = 30;
        const stepDuration = duration / steps;
        const increment = (endValue - startValue) / steps;
        
        let currentStep = 0;
        const timer = setInterval(() => {
            currentStep++;
            this.displayValue = Math.round(startValue + (increment * currentStep));
            
            if (currentStep >= steps) {
                clearInterval(timer);
                this.displayValue = endValue;
            }
        }, stepDuration);
    },
    
    get trend() {
        if (!this.previousValue) return 0;
        return ((this.value - this.previousValue) / this.previousValue * 100).toFixed(1);
    },
    
    get trendPositive() {
        return this.trend >= 0;
    },
    
    get formattedValue() {
        if (this.$el.dataset.format === 'currency') {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(this.displayValue);
        }
        
        if (this.$el.dataset.format === 'duration') {
            const mins = Math.floor(this.displayValue / 60);
            const secs = this.displayValue % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        return this.displayValue.toLocaleString('de-DE');
    },
    
    refresh() {
        this.loading = true;
        // Parent component should handle data refresh
        this.$dispatch('stats-refresh');
    }
});