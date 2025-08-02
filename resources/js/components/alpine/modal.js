export default () => ({
    open: false,
    size: 'md', // sm, md, lg, xl, full
    title: '',
    closable: true,
    
    init() {
        // Handle escape key
        this.$watch('open', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => {
                    this.$refs.modal?.focus();
                });
            } else {
                document.body.style.overflow = '';
            }
        });
    },
    
    show(title = '', size = 'md') {
        this.title = title;
        this.size = size;
        this.open = true;
    },
    
    hide() {
        if (this.closable) {
            this.open = false;
            this.$dispatch('modal-closed');
        }
    },
    
    handleKeydown(event) {
        if (event.key === 'Escape' && this.closable) {
            this.hide();
        }
    },
    
    handleBackdropClick(event) {
        if (event.target === event.currentTarget && this.closable) {
            this.hide();
        }
    },
    
    confirm(message, onConfirm, onCancel = null) {
        this.title = 'Bestätigung';
        this.open = true;
        
        // Set content dynamically
        this.$nextTick(() => {
            const content = this.$refs.content;
            if (content) {
                content.innerHTML = `
                    <p class="text-gray-700 mb-4">${message}</p>
                    <div class="flex justify-end gap-2">
                        <button @click="handleCancel" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            Abbrechen
                        </button>
                        <button @click="handleConfirm" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Bestätigen
                        </button>
                    </div>
                `;
            }
        });
        
        this.handleConfirm = () => {
            this.hide();
            if (onConfirm) onConfirm();
        };
        
        this.handleCancel = () => {
            this.hide();
            if (onCancel) onCancel();
        };
    }
});