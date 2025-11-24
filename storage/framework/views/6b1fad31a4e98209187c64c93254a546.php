<div class="p-4 space-y-4">
    <?php
        $sampleData = [
            'name' => 'Max Mustermann',
            'date' => now()->addDays(2)->format('d.m.Y'),
            'time' => '14:30',
            'location' => 'Hauptfiliale Berlin',
            'service' => 'Premium Beratung',
            'employee' => 'Anna Schmidt',
            'amount' => '89,99',
            'customer_name' => 'Max Mustermann',
            'appointment_date' => now()->addDays(2)->format('d.m.Y'),
            'appointment_time' => '14:30',
            'branch_name' => 'Hauptfiliale Berlin',
            'service_name' => 'Premium Beratung',
            'staff_name' => 'Anna Schmidt',
            'company_name' => 'Beispiel GmbH',
            'company_phone' => '+49 30 12345678',
            'duration' => '60',
            'price' => '89,99 €',
        ];

        $content = $template->content ?? [];
        $subject = $template->subject ?? [];
    ?>

    <div class="space-y-4">
        
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold">Kanal:</span>
            <!--[if BLOCK]><![endif]--><?php switch($template->channel):
                case ('email'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-envelope'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3 h-3']); ?>
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
                        E-Mail
                    </span>
                    <?php break; ?>
                <?php case ('sms'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-device-phone-mobile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3 h-3']); ?>
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
                        SMS
                    </span>
                    <?php break; ?>
                <?php case ('whatsapp'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chat-bubble-left-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3 h-3']); ?>
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
                        WhatsApp
                    </span>
                    <?php break; ?>
                <?php case ('push'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell-alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3 h-3']); ?>
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
                        Push
                    </span>
                    <?php break; ?>
            <?php endswitch; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        
        <div x-data="{ activeTab: 'de' }" class="space-y-4">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'de'"
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'de', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'de' }"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        🇩🇪 Deutsch
                    </button>
                    <button
                        @click="activeTab = 'en'"
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'en', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'en' }"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        🇬🇧 English
                    </button>
                </nav>
            </div>

            
            <div x-show="activeTab === 'de'" class="space-y-3">
                <!--[if BLOCK]><![endif]--><?php if($template->channel === 'email' && isset($subject['de'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Betreff</div>
                        <div class="text-gray-900 font-medium">
                            <?php
                                $previewSubject = $subject['de'];
                                foreach($sampleData as $key => $value) {
                                    $previewSubject = str_replace('{'.$key.'}', $value, $previewSubject);
                                    $previewSubject = str_replace('{{'.$key.'}}', $value, $previewSubject);
                                }
                                echo $previewSubject;
                            ?>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Inhalt</div>
                    <div class="prose prose-sm max-w-none">
                        <?php
                            $previewContent = $content['de'] ?? 'Kein deutscher Inhalt verfügbar.';
                            foreach($sampleData as $key => $value) {
                                $previewContent = str_replace('{'.$key.'}', '<span class="bg-yellow-100 px-1 rounded">' . $value . '</span>', $previewContent);
                                $previewContent = str_replace('{{'.$key.'}}', '<span class="bg-yellow-100 px-1 rounded">' . $value . '</span>', $previewContent);
                            }
                            // Convert line breaks to HTML
                            $previewContent = nl2br($previewContent);
                        ?>
                        <?php echo $previewContent; ?>

                    </div>
                </div>
            </div>

            
            <div x-show="activeTab === 'en'" class="space-y-3">
                <!--[if BLOCK]><![endif]--><?php if($template->channel === 'email' && isset($subject['en'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Subject</div>
                        <div class="text-gray-900 font-medium">
                            <?php
                                $previewSubject = $subject['en'];
                                foreach($sampleData as $key => $value) {
                                    $previewSubject = str_replace('{'.$key.'}', $value, $previewSubject);
                                    $previewSubject = str_replace('{{'.$key.'}}', $value, $previewSubject);
                                }
                                echo $previewSubject;
                            ?>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Content</div>
                    <div class="prose prose-sm max-w-none">
                        <?php
                            $previewContent = $content['en'] ?? 'No English content available.';
                            foreach($sampleData as $key => $value) {
                                $previewContent = str_replace('{'.$key.'}', '<span class="bg-yellow-100 px-1 rounded">' . $value . '</span>', $previewContent);
                                $previewContent = str_replace('{{'.$key.'}}', '<span class="bg-yellow-100 px-1 rounded">' . $value . '</span>', $previewContent);
                            }
                            // Convert line breaks to HTML
                            $previewContent = nl2br($previewContent);
                        ?>
                        <?php echo $previewContent; ?>

                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-blue-50 rounded-lg p-4 mt-4">
            <div class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-2">Verwendete Beispieldaten</div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = array_slice($sampleData, 0, 10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-2">
                        <code class="bg-blue-100 px-1.5 py-0.5 rounded text-blue-700"><?php echo e('{'.$key.'}'); ?></code>
                        <span class="text-gray-600">→</span>
                        <span class="text-gray-700"><?php echo e($value); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>
    </div>
</div><?php /**PATH /var/www/api-gateway/resources/views/filament/notifications/template-preview.blade.php ENDPATH**/ ?>