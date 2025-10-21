<?php if (isset($component)) { $__componentOriginalb525200bfa976483b4eaa0b7685c6e24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
            <div class="flex items-center gap-2">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bolt'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-primary-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                <span><?php echo e($heading); ?></span>
            </div>
         <?php $__env->endSlot(); ?>

         <?php $__env->slot('description', null, []); ?> 
            Häufig verwendete Aktionen für schnellen Zugriff
         <?php $__env->endSlot(); ?>

        <!--[if BLOCK]><![endif]--><?php if(empty($actions)): ?>
            <div class="text-center text-gray-500 py-8">
                <p>Keine Schnellaktionen verfügbar</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        // Safe color mapping to avoid dynamic Tailwind classes
                        $colorClasses = [
                            'success' => [
                                'bg' => 'bg-success-100 dark:bg-success-900/20',
                                'hover' => 'hover:bg-success-200 dark:hover:bg-success-900/30',
                                'text' => 'text-success-600 dark:text-success-400',
                                'hover_text' => 'hover:text-success-600 dark:hover:text-success-400',
                                'border' => 'hover:border-success-500',
                                'badge' => 'bg-success-500'
                            ],
                            'primary' => [
                                'bg' => 'bg-primary-100 dark:bg-primary-900/20',
                                'hover' => 'hover:bg-primary-200 dark:hover:bg-primary-900/30',
                                'text' => 'text-primary-600 dark:text-primary-400',
                                'hover_text' => 'hover:text-primary-600 dark:hover:text-primary-400',
                                'border' => 'hover:border-primary-500',
                                'badge' => 'bg-primary-500'
                            ],
                            'info' => [
                                'bg' => 'bg-info-100 dark:bg-info-900/20',
                                'hover' => 'hover:bg-info-200 dark:hover:bg-info-900/30',
                                'text' => 'text-info-600 dark:text-info-400',
                                'hover_text' => 'hover:text-info-600 dark:hover:text-info-400',
                                'border' => 'hover:border-info-500',
                                'badge' => 'bg-info-500'
                            ],
                            'warning' => [
                                'bg' => 'bg-warning-100 dark:bg-warning-900/20',
                                'hover' => 'hover:bg-warning-200 dark:hover:bg-warning-900/30',
                                'text' => 'text-warning-600 dark:text-warning-400',
                                'hover_text' => 'hover:text-warning-600 dark:hover:text-warning-400',
                                'border' => 'hover:border-warning-500',
                                'badge' => 'bg-warning-500'
                            ],
                            'purple' => [
                                'bg' => 'bg-purple-100 dark:bg-purple-900/20',
                                'hover' => 'hover:bg-purple-200 dark:hover:bg-purple-900/30',
                                'text' => 'text-purple-600 dark:text-purple-400',
                                'hover_text' => 'hover:text-purple-600 dark:hover:text-purple-400',
                                'border' => 'hover:border-purple-500',
                                'badge' => 'bg-purple-500'
                            ],
                            'gray' => [
                                'bg' => 'bg-gray-100 dark:bg-gray-900/20',
                                'hover' => 'hover:bg-gray-200 dark:hover:bg-gray-900/30',
                                'text' => 'text-gray-600 dark:text-gray-400',
                                'hover_text' => 'hover:text-gray-600 dark:hover:text-gray-400',
                                'border' => 'hover:border-gray-500',
                                'badge' => 'bg-gray-500'
                            ],
                        ];

                        $color = $action['color'] ?? 'gray';
                        $classes = $colorClasses[$color] ?? $colorClasses['gray'];
                    ?>

                    <a
                        href="<?php echo e($action['url'] ?? '#'); ?>"
                        class="group relative flex flex-col items-center justify-center p-4 text-center rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 <?php echo e($classes['border']); ?> transition-all duration-200 hover:shadow-lg hover:scale-105"
                    >
                        <!--[if BLOCK]><![endif]--><?php if(!empty($action['badge'])): ?>
                            <span class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white <?php echo e($classes['badge']); ?> rounded-full">
                                <?php echo e($action['badge']); ?>

                            </span>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <div class="mb-2">
                            <div class="w-12 h-12 mx-auto flex items-center justify-center rounded-full <?php echo e($classes['bg']); ?> <?php echo e($classes['hover']); ?> transition-colors">
                                <!--[if BLOCK]><![endif]--><?php if(!empty($action['icon'])): ?>
                                    <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $action['icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-6 h-6 '.e($classes['text']).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-squares-2x2'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-6 h-6 '.e($classes['text']).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white <?php echo e($classes['hover_text']); ?>">
                                <?php echo e($action['label'] ?? 'Aktion'); ?>

                            </h3>
                            <!--[if BLOCK]><![endif]--><?php if(!empty($action['description'])): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo e($action['description']); ?>

                                </p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
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
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $attributes = $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $component = $__componentOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?><?php /**PATH /var/www/api-gateway/resources/views/filament/widgets/quick-actions.blade.php ENDPATH**/ ?>