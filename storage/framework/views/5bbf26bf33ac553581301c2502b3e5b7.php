<?php
    $record = $getRecord();
    $isOverdue = $record->is_overdue;
    $priority = $record->priority;

    // Determine urgency level (0 = most urgent, 3 = least urgent)
    $urgencyLevel = match(true) {
        $isOverdue && $priority === 'urgent' => 0,
        $isOverdue => 1,
        $priority === 'urgent' => 2,
        $priority === 'high' => 3,
        default => 4,
    };

    $color = match($urgencyLevel) {
        0, 1 => 'danger',
        2 => 'warning',
        3 => 'warning',
        default => 'gray',
    };

    $icon = match($urgencyLevel) {
        0 => 'heroicon-o-fire',
        1 => 'heroicon-o-exclamation-triangle',
        2 => 'heroicon-o-exclamation-triangle',
        3 => 'heroicon-o-arrow-up-circle',
        default => 'heroicon-o-minus-circle',
    };

    $pulse = in_array($urgencyLevel, [0, 1]); // Pulse animation for critical

    $tooltip = match($urgencyLevel) {
        0 => 'KRITISCH: Überfällig & Dringend',
        1 => 'ÜBERFÄLLIG',
        2 => 'Dringend',
        3 => 'Hohe Priorität',
        default => 'Normale Priorität',
    };
?>

<div class="flex items-center justify-center" title="<?php echo e($tooltip); ?>">
    <div class="relative inline-flex">
        <!--[if BLOCK]><![endif]--><?php if($pulse): ?>
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-<?php echo e($color); ?>-400 opacity-75"></span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <div class="relative inline-flex items-center justify-center">
            <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $icon,'class' => \Illuminate\Support\Arr::toCssClasses([
                    'h-6 w-6',
                    'text-danger-500' => $color === 'danger',
                    'text-warning-500' => $color === 'warning',
                    'text-gray-400' => $color === 'gray',
                ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses([
                    'h-6 w-6',
                    'text-danger-500' => $color === 'danger',
                    'text-warning-500' => $color === 'warning',
                    'text-gray-400' => $color === 'gray',
                ]))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /var/www/api-gateway/resources/views/filament/tables/columns/callback-urgency.blade.php ENDPATH**/ ?>