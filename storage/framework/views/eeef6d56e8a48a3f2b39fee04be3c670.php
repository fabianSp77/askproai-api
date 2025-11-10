<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div
        x-data="{
            selectedBranchId: <?php if ((object) ('selectedBranchId') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selectedBranchId'->value()); ?>')<?php echo e('selectedBranchId'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selectedBranchId'); ?>')<?php endif; ?>,
            branches: <?php if ((object) ('branches') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('branches'->value()); ?>')<?php echo e('branches'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('branches'); ?>')<?php endif; ?>,
            isLoading: false,
            error: null,

            init() {
                // Load branches on component initialization
                this.$wire.loadBranches();

                // Load saved branch from localStorage
                const savedBranchId = localStorage.getItem('calcom_selected_branch_id');
                if (savedBranchId && this.selectedBranchId === null) {
                    this.selectedBranchId = parseInt(savedBranchId);
                }

                // Listen for branch changes to update Cal.com widget
                this.$watch('selectedBranchId', (newBranchId) => {
                    if (newBranchId) {
                        localStorage.setItem('calcom_selected_branch_id', newBranchId);
                        this.reloadCalcomWidget(newBranchId);
                    }
                });
            },

            selectBranch(branchId) {
                this.selectedBranchId = branchId;
                this.$wire.selectBranch(branchId);
            },

            reloadCalcomWidget(branchId) {
                this.isLoading = true;
                this.error = null;

                try {
                    // Update CalcomConfig for widget reload
                    if (window.CalcomConfig) {
                        window.CalcomConfig.defaultBranchId = branchId;
                    }

                    // Reload the Cal.com Atoms widget
                    const bookerElement = document.querySelector('[data-calcom-booker]');
                    if (bookerElement && window.Cal) {
                        // Trigger widget reload by updating data attribute
                        const currentConfig = JSON.parse(bookerElement.getAttribute('data-calcom-booker'));
                        currentConfig.initialBranchId = branchId;
                        bookerElement.setAttribute('data-calcom-booker', JSON.stringify(currentConfig));

                        // Force re-initialization
                        if (window.Cal.reload) {
                            window.Cal.reload();
                        }
                    }

                } catch (error) {
                    console.error('[BranchSelector] Failed to reload Cal.com widget:', error);
                    this.error = 'Failed to load booking widget. Please refresh the page.';
                } finally {
                    this.isLoading = false;
                }
            },

            getBranchName(branchId) {
                const branch = this.branches.find(b => b.id === branchId);
                return branch ? branch.name : 'Select Branch';
            },

            getBranchServicesCount(branchId) {
                const branch = this.branches.find(b => b.id === branchId);
                return branch ? branch.services_count : 0;
            }
        }"
        class="space-y-6"
    >
        
        <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
             <?php $__env->slot('heading', null, []); ?> 
                Quick Start
             <?php $__env->endSlot(); ?>
             <?php $__env->slot('description', null, []); ?> 
                Select a branch, choose a service, and pick an available time slot.
             <?php $__env->endSlot(); ?>

            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <p>✓ Real-time availability from Cal.com</p>
                <p>✓ Automatic sync with your calendar</p>
                <p>✓ Customer notifications handled automatically</p>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>

        
        <!--[if BLOCK]><![endif]--><?php if($this->isAdmin() && count($branches) > 1): ?>
            <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                 <?php $__env->slot('heading', null, []); ?> 
                    Select Branch
                 <?php $__env->endSlot(); ?>
                 <?php $__env->slot('description', null, []); ?> 
                    Choose the branch location for booking appointments.
                 <?php $__env->endSlot(); ?>

                <div class="space-y-4">
                    
                    <div class="relative">
                        <label for="branch-selector" class="sr-only">Select Branch</label>
                        <select
                            id="branch-selector"
                            x-model="selectedBranchId"
                            @change="selectBranch($event.target.value)"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        >
                            <option value="">Select a branch...</option>
                            <template x-for="branch in branches" :key="branch.id">
                                <option
                                    :value="branch.id"
                                    x-text="`${branch.name} (${branch.services_count} services)`"
                                ></option>
                            </template>
                        </select>
                    </div>

                    
                    <div x-show="isLoading" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Loading booking widget...</span>
                    </div>

                    
                    <div
                        x-show="error"
                        x-text="error"
                        class="rounded-lg bg-danger-50 dark:bg-danger-900/20 p-4 text-sm text-danger-800 dark:text-danger-200"
                    ></div>

                    
                    <div
                        x-show="selectedBranchId && !isLoading"
                        class="rounded-lg bg-primary-50 dark:bg-primary-900/20 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-primary-600 dark:text-primary-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-primary-900 dark:text-primary-100" x-text="getBranchName(selectedBranchId)"></p>
                                <p class="text-sm text-primary-700 dark:text-primary-300" x-text="`${getBranchServicesCount(selectedBranchId)} available services`"></p>
                            </div>
                        </div>
                    </div>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
             <?php $__env->slot('heading', null, []); ?> 
                Book Appointment
             <?php $__env->endSlot(); ?>

            
            <div class="relative min-h-[600px]">
                
                <div
                    x-show="isLoading"
                    class="absolute inset-0 bg-white/75 dark:bg-gray-900/75 flex items-center justify-center z-10 rounded-lg"
                >
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Loading booking widget...</p>
                    </div>
                </div>

                
                <div
                    data-calcom-booker='<?php echo json_encode([
                        "initialBranchId" => auth()->user()->branch_id ?? null, "layout" => "MONTH_VIEW", "enableBranchSelector" => false  // Handled by our custom selector
                    ]) ?>'
                    x-bind:data-branch-id="selectedBranchId"
                ></div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
    </div>

    <?php if (isset($component)) { $__componentOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.calcom-scripts','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('calcom-scripts'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb)): ?>
<?php $attributes = $__attributesOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb; ?>
<?php unset($__attributesOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb)): ?>
<?php $component = $__componentOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb; ?>
<?php unset($__componentOriginalcdd5cbf6dbc8a8759e068d8e2d5b21bb); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH /var/www/api-gateway/resources/views/filament/pages/calcom-booking.blade.php ENDPATH**/ ?>