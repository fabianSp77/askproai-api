// Agent Management Interactive Features
document.addEventListener('DOMContentLoaded', function() {
    // Voice preview functionality
    const setupVoicePreview = () => {
        const voicePreviewButtons = document.querySelectorAll('[data-voice-preview]');
        
        voicePreviewButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const voiceId = button.dataset.voicePreview;
                const text = button.dataset.previewText || 'Hallo, ich bin Ihr persönlicher Assistent.';
                
                // Add loading state
                button.classList.add('opacity-50', 'cursor-wait');
                button.disabled = true;
                
                try {
                    // This would call a preview API endpoint
                    console.log(`Preview voice: ${voiceId} with text: ${text}`);
                    // Simulated delay
                    await new Promise(resolve => setTimeout(resolve, 2000));
                } catch (error) {
                    console.error('Voice preview failed:', error);
                } finally {
                    button.classList.remove('opacity-50', 'cursor-wait');
                    button.disabled = false;
                }
            });
        });
    };
    
    // Prompt syntax highlighting
    const setupPromptHighlighting = () => {
        const promptEditors = document.querySelectorAll('.prompt-editor');
        
        promptEditors.forEach(editor => {
            editor.addEventListener('input', (e) => {
                // Simple highlighting for variables
                let content = e.target.value;
                const variables = content.match(/\{\{[^}]+\}\}/g) || [];
                
                variables.forEach(variable => {
                    console.log(`Found variable: ${variable}`);
                });
            });
        });
    };
    
    // Agent card interactions
    const setupAgentCards = () => {
        const agentCards = document.querySelectorAll('.agent-card');
        
        agentCards.forEach(card => {
            // Add hover effects
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-2px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
            
            // Handle edit mode
            const editButtons = card.querySelectorAll('[data-edit-field]');
            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    card.classList.add('is-editing');
                });
            });
        });
    };
    
    // Drag and drop for branch assignment
    const setupDragAndDrop = () => {
        const draggableAgents = document.querySelectorAll('[data-draggable-agent]');
        const dropZones = document.querySelectorAll('[data-drop-zone-branch]');
        
        draggableAgents.forEach(agent => {
            agent.draggable = true;
            
            agent.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('agentId', agent.dataset.draggableAgent);
                agent.classList.add('opacity-50');
            });
            
            agent.addEventListener('dragend', () => {
                agent.classList.remove('opacity-50');
            });
        });
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                zone.classList.add('bg-primary-50', 'border-primary-500');
            });
            
            zone.addEventListener('dragleave', () => {
                zone.classList.remove('bg-primary-50', 'border-primary-500');
            });
            
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                const agentId = e.dataTransfer.getData('agentId');
                const branchId = zone.dataset.dropZoneBranch;
                
                zone.classList.remove('bg-primary-50', 'border-primary-500');
                
                // Trigger Livewire event
                if (window.Livewire) {
                    window.Livewire.emit('assignAgentToBranch', agentId, branchId);
                }
            });
        });
    };
    
    // Copy agent configuration
    const setupCopyConfig = () => {
        const copyButtons = document.querySelectorAll('[data-copy-agent-config]');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const agentId = button.dataset.copyAgentConfig;
                const configElement = document.querySelector(`[data-agent-config="${agentId}"]`);
                
                if (configElement) {
                    const config = configElement.textContent;
                    
                    try {
                        await navigator.clipboard.writeText(config);
                        
                        // Show success feedback
                        const originalText = button.innerHTML;
                        button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                        button.classList.add('text-green-600');
                        
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('text-green-600');
                        }, 2000);
                    } catch (err) {
                        console.error('Failed to copy:', err);
                    }
                }
            });
        });
    };
    
    // Search and filter agents
    const setupAgentSearch = () => {
        const searchInput = document.querySelector('[data-agent-search]');
        const filterButtons = document.querySelectorAll('[data-agent-filter]');
        const agentCards = document.querySelectorAll('.agent-card');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                
                agentCards.forEach(card => {
                    const agentName = card.dataset.agentName?.toLowerCase() || '';
                    const agentId = card.dataset.agentId?.toLowerCase() || '';
                    
                    if (agentName.includes(searchTerm) || agentId.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.agentFilter;
                
                // Update active state
                filterButtons.forEach(btn => btn.classList.remove('bg-primary-100', 'text-primary-700'));
                button.classList.add('bg-primary-100', 'text-primary-700');
                
                // Apply filter
                agentCards.forEach(card => {
                    switch (filter) {
                        case 'all':
                            card.style.display = '';
                            break;
                        case 'assigned':
                            card.style.display = card.dataset.isAssigned === 'true' ? '' : 'none';
                            break;
                        case 'unassigned':
                            card.style.display = card.dataset.isAssigned === 'false' ? '' : 'none';
                            break;
                        default:
                            card.style.display = '';
                    }
                });
            });
        });
    };
    
    // Initialize all features
    const init = () => {
        setupVoicePreview();
        setupPromptHighlighting();
        setupAgentCards();
        setupDragAndDrop();
        setupCopyConfig();
        setupAgentSearch();
    };
    
    // Run initialization
    init();
    
    // Re-initialize on Livewire updates
    if (window.Livewire) {
        window.Livewire.on('agentsUpdated', () => {
            setTimeout(init, 100);
        });
    }
});