// Virtual Scroller for handling large datasets efficiently
export class VirtualScroller {
    constructor(container, options = {}) {
        this.container = container;
        this.itemHeight = options.itemHeight || 50;
        this.buffer = options.buffer || 5;
        this.items = [];
        this.visibleItems = [];
        this.scrollTop = 0;
        this.containerHeight = 0;
        
        this.init();
    }
    
    init() {
        // Create scroll container
        this.scrollContainer = document.createElement('div');
        this.scrollContainer.className = 'virtual-scroll-container';
        this.scrollContainer.style.position = 'relative';
        this.scrollContainer.style.overflow = 'auto';
        this.scrollContainer.style.height = '100%';
        
        // Create content container
        this.contentContainer = document.createElement('div');
        this.contentContainer.className = 'virtual-scroll-content';
        this.contentContainer.style.position = 'relative';
        
        // Move existing content
        while (this.container.firstChild) {
            this.contentContainer.appendChild(this.container.firstChild);
        }
        
        this.scrollContainer.appendChild(this.contentContainer);
        this.container.appendChild(this.scrollContainer);
        
        // Setup event listeners
        this.scrollContainer.addEventListener('scroll', this.handleScroll.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // Initial setup
        this.updateContainerHeight();
        this.loadItems();
        this.render();
    }
    
    handleScroll(e) {
        this.scrollTop = e.target.scrollTop;
        requestAnimationFrame(() => this.render());
        
        // Prefetch on hover logic
        if (this.isNearBottom()) {
            this.prefetchNext();
        }
    }
    
    handleResize() {
        this.updateContainerHeight();
        this.render();
    }
    
    updateContainerHeight() {
        this.containerHeight = this.scrollContainer.clientHeight;
    }
    
    loadItems() {
        // Get all rows
        const rows = this.contentContainer.querySelectorAll('tr[data-record-id]');
        this.items = Array.from(rows).map((row, index) => ({
            id: row.dataset.recordId,
            element: row,
            height: this.itemHeight,
            top: index * this.itemHeight
        }));
        
        // Set total height
        const totalHeight = this.items.length * this.itemHeight;
        this.contentContainer.style.height = `${totalHeight}px`;
    }
    
    render() {
        const scrollTop = this.scrollTop;
        const startIndex = Math.floor(scrollTop / this.itemHeight) - this.buffer;
        const endIndex = Math.ceil((scrollTop + this.containerHeight) / this.itemHeight) + this.buffer;
        
        // Ensure indices are within bounds
        const safeStartIndex = Math.max(0, startIndex);
        const safeEndIndex = Math.min(this.items.length - 1, endIndex);
        
        // Hide all items first
        this.items.forEach(item => {
            item.element.style.display = 'none';
        });
        
        // Show and position visible items
        for (let i = safeStartIndex; i <= safeEndIndex; i++) {
            const item = this.items[i];
            if (item) {
                item.element.style.display = '';
                item.element.style.position = 'absolute';
                item.element.style.top = `${item.top}px`;
                item.element.style.left = '0';
                item.element.style.right = '0';
            }
        }
        
        this.visibleItems = this.items.slice(safeStartIndex, safeEndIndex + 1);
    }
    
    isNearBottom() {
        const scrollBottom = this.scrollTop + this.containerHeight;
        const totalHeight = this.items.length * this.itemHeight;
        return scrollBottom > totalHeight - (this.containerHeight * 2);
    }
    
    prefetchNext() {
        // Emit event to load more data
        const event = new CustomEvent('virtual-scroll-prefetch', {
            detail: {
                currentCount: this.items.length,
                visibleCount: this.visibleItems.length
            }
        });
        this.container.dispatchEvent(event);
    }
    
    updateItems(newItems) {
        // Append new items
        newItems.forEach((item, index) => {
            const newIndex = this.items.length + index;
            this.items.push({
                id: item.id,
                element: item.element,
                height: this.itemHeight,
                top: newIndex * this.itemHeight
            });
        });
        
        // Update total height
        const totalHeight = this.items.length * this.itemHeight;
        this.contentContainer.style.height = `${totalHeight}px`;
        
        // Re-render
        this.render();
    }
    
    scrollToItem(itemId) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            this.scrollContainer.scrollTop = item.top - (this.containerHeight / 2) + (this.itemHeight / 2);
        }
    }
    
    destroy() {
        this.scrollContainer.removeEventListener('scroll', this.handleScroll);
        window.removeEventListener('resize', this.handleResize);
    }
}

// Optimized virtual scroller for extremely large datasets
export class OptimizedVirtualScroller extends VirtualScroller {
    constructor(container, options = {}) {
        super(container, options);
        this.chunkSize = options.chunkSize || 100;
        this.chunks = new Map();
        this.loadedChunks = new Set();
    }
    
    async loadChunk(chunkIndex) {
        if (this.loadedChunks.has(chunkIndex)) {
            return this.chunks.get(chunkIndex);
        }
        
        // Simulate async chunk loading
        const startIndex = chunkIndex * this.chunkSize;
        const endIndex = Math.min(startIndex + this.chunkSize, this.totalItems);
        
        const chunk = await this.fetchChunk(startIndex, endIndex);
        this.chunks.set(chunkIndex, chunk);
        this.loadedChunks.add(chunkIndex);
        
        return chunk;
    }
    
    async fetchChunk(start, end) {
        // This would be an actual API call
        return new Promise(resolve => {
            setTimeout(() => {
                const items = [];
                for (let i = start; i < end; i++) {
                    items.push({
                        id: `item-${i}`,
                        data: `Item ${i}`
                    });
                }
                resolve(items);
            }, 100);
        });
    }
    
    async render() {
        const scrollTop = this.scrollTop;
        const startIndex = Math.floor(scrollTop / this.itemHeight) - this.buffer;
        const endIndex = Math.ceil((scrollTop + this.containerHeight) / this.itemHeight) + this.buffer;
        
        // Determine which chunks we need
        const startChunk = Math.floor(startIndex / this.chunkSize);
        const endChunk = Math.floor(endIndex / this.chunkSize);
        
        // Load required chunks
        const chunkPromises = [];
        for (let i = startChunk; i <= endChunk; i++) {
            if (!this.loadedChunks.has(i)) {
                chunkPromises.push(this.loadChunk(i));
            }
        }
        
        if (chunkPromises.length > 0) {
            await Promise.all(chunkPromises);
        }
        
        // Now render as normal
        super.render();
    }
}