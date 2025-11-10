<?php if (! $__env->hasRenderedOnce('2c6d61bb-7396-464c-a3f5-2262d98e5ab8')): $__env->markAsRenderedOnce('2c6d61bb-7396-464c-a3f5-2262d98e5ab8'); ?>
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/calcom-atoms.jsx', 'resources/css/calcom-atoms.css']); ?>

    <script>
        // Global Cal.com configuration
        window.CalcomConfig = {
            teamId: <?php echo e(config('calcom.team_id')); ?>,
            teamSlug: '<?php echo e(config('calcom.team_slug')); ?>',
            apiUrl: '<?php echo e(url('')); ?>', // Use our Laravel proxy (root-level routes in web.php)
            defaultBranchId: <?php echo e(auth()->user()?->branch_id ?? 'null'); ?>,  // For company_manager with assigned branch
            companyId: <?php echo e(auth()->user()?->company_id ?? 'null'); ?>,
            layout: 'MONTH_VIEW',  // Will be replaced with user preference in Phase 4
            autoSelectSingleBranch: true,  // Auto-select if user has only one branch
        };
    </script>
<?php endif; ?>
<?php /**PATH /var/www/api-gateway/resources/views/components/calcom-scripts.blade.php ENDPATH**/ ?>