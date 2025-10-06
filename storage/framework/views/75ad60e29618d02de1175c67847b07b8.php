<?php
    $profitData = $costCalculator->getDisplayProfit($call, auth()->user());
    $user = auth()->user();
    $isSuperAdmin = $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
    $isReseller = $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);

    // Format currency helper
    $formatCurrency = function($cents) {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    };

    // Calculate percentages
    $calculatePercentage = function($value, $base) {
        if ($base <= 0) return 0;
        return round(($value / $base) * 100, 2);
    };

    // Get appointment revenue
    $revenue = $call->getAppointmentRevenue();
    $hasRevenue = $revenue > 0;
?>

<div class="space-y-4 p-2 sm:p-4">
    
    <div class="text-center pb-3 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-center gap-2 mb-2">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">
                Finanzielle Details
            </h3>
        </div>
        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
            Anruf-ID: <?php echo e($call->external_id ?? $call->id); ?>

        </p>
    </div>

    
    <!--[if BLOCK]><![endif]--><?php if($hasRevenue): ?>
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-3 sm:p-4">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm sm:text-base">Termin-Einnahmen</h4>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Gebuchter Termin:</span>
            <span class="font-mono text-lg sm:text-2xl font-bold text-blue-700 dark:text-blue-300">
                <?php echo e($formatCurrency($revenue)); ?>

            </span>
        </div>
    </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 sm:p-4">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm sm:text-base">Kostenübersicht</h4>
        </div>
        <div class="space-y-2 sm:space-y-3">
            
            <!--[if BLOCK]><![endif]--><?php if($isSuperAdmin): ?>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Basiskosten:</span>
                    <span class="font-mono text-xs sm:text-sm font-medium"><?php echo e($formatCurrency($call->base_cost ?? 0)); ?></span>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php if(($isSuperAdmin || $isReseller) && $call->reseller_cost > 0): ?>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        <!--[if BLOCK]><![endif]--><?php if($isReseller && !$isSuperAdmin): ?>
                            Meine Kosten:
                        <?php else: ?>
                            Mandanten-Kosten:
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </span>
                    <span class="font-mono text-xs sm:text-sm font-medium"><?php echo e($formatCurrency($call->reseller_cost ?? 0)); ?></span>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            <div class="flex justify-between items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">Kunden-Kosten:</span>
                <span class="font-mono text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100"><?php echo e($formatCurrency($call->customer_cost ?? 0)); ?></span>
            </div>
        </div>
    </div>

    
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
        <h4 class="font-medium text-green-700 dark:text-green-300 mb-3">💵 Profit-Aufschlüsselung</h4>
        <div class="space-y-3">
            <!--[if BLOCK]><![endif]--><?php if($isSuperAdmin): ?>
                
                <!--[if BLOCK]><![endif]--><?php if($call->platform_profit !== null): ?>
                    <div class="flex justify-between items-center pb-2 border-b border-green-200 dark:border-green-800">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Platform-Profit:</span>
                            <span class="text-xs block text-gray-500 dark:text-gray-500">Unser Gewinn</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-sm font-semibold text-green-600 dark:text-green-400">
                                +<?php echo e($formatCurrency($call->platform_profit)); ?>

                            </span>
                            <span class="text-xs block text-gray-500 dark:text-gray-500">
                                (<?php echo e($call->profit_margin_platform); ?>%)
                            </span>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($call->reseller_profit > 0): ?>
                    <div class="flex justify-between items-center pb-2 border-b border-green-200 dark:border-green-800">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Mandanten-Profit:</span>
                            <span class="text-xs block text-gray-500 dark:text-gray-500">Gewinn des Mandanten</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400">
                                +<?php echo e($formatCurrency($call->reseller_profit)); ?>

                            </span>
                            <span class="text-xs block text-gray-500 dark:text-gray-500">
                                (<?php echo e($call->profit_margin_reseller); ?>%)
                            </span>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <div class="flex justify-between items-center pt-2">
                    <div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">GESAMT-PROFIT:</span>
                        <span class="text-xs block text-gray-500 dark:text-gray-500">Kompletter Gewinn</span>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-lg font-bold text-green-700 dark:text-green-300">
                            +<?php echo e($formatCurrency($call->total_profit ?? 0)); ?>

                        </span>
                        <span class="text-xs block font-semibold text-green-600 dark:text-green-400">
                            (<?php echo e($call->profit_margin_total); ?>%)
                        </span>
                    </div>
                </div>
            <?php elseif($isReseller): ?>
                
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ihr Profit:</span>
                        <span class="text-xs block text-gray-500 dark:text-gray-500">Mandanten-Gewinn</span>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-lg font-bold text-green-700 dark:text-green-300">
                            +<?php echo e($formatCurrency($profitData['profit'])); ?>

                        </span>
                        <span class="text-xs block font-semibold text-green-600 dark:text-green-400">
                            (<?php echo e($profitData['margin']); ?>%)
                        </span>
                    </div>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>

    
    <!--[if BLOCK]><![endif]--><?php if($call->customer_cost > 0): ?>
        
        <!--[if BLOCK]><![endif]--><?php if($isSuperAdmin): ?>
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">📈 Profit-Verteilung (Komplett)</h4>
                <div class="relative">
                    <div class="flex h-8 overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-700">
                        <?php
                            $baseCostPercent = ($call->base_cost / $call->customer_cost) * 100;
                            $platformProfitPercent = ($call->platform_profit / $call->customer_cost) * 100;
                            $resellerProfitPercent = ($call->reseller_profit / $call->customer_cost) * 100;
                        ?>

                        
                        <div class="bg-gray-500 flex items-center justify-center text-white text-xs font-medium"
                             style="width: <?php echo e($baseCostPercent); ?>%"
                             title="Basiskosten: <?php echo e($formatCurrency($call->base_cost)); ?>">
                            <!--[if BLOCK]><![endif]--><?php if($baseCostPercent > 15): ?>
                                <?php echo e(round($baseCostPercent)); ?>%
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <!--[if BLOCK]><![endif]--><?php if($call->platform_profit > 0): ?>
                            <div class="bg-green-500 flex items-center justify-center text-white text-xs font-medium"
                                 style="width: <?php echo e($platformProfitPercent); ?>%"
                                 title="Platform-Profit: <?php echo e($formatCurrency($call->platform_profit)); ?>">
                                <!--[if BLOCK]><![endif]--><?php if($platformProfitPercent > 15): ?>
                                    <?php echo e(round($platformProfitPercent)); ?>%
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($call->reseller_profit > 0): ?>
                            <div class="bg-blue-500 flex items-center justify-center text-white text-xs font-medium"
                                 style="width: <?php echo e($resellerProfitPercent); ?>%"
                                 title="Mandanten-Profit: <?php echo e($formatCurrency($call->reseller_profit)); ?>">
                                <!--[if BLOCK]><![endif]--><?php if($resellerProfitPercent > 15): ?>
                                    <?php echo e(round($resellerProfitPercent)); ?>%
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    
                    <div class="mt-2 flex justify-center space-x-4 text-xs">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-gray-500 rounded mr-1"></div>
                            <span class="text-gray-600 dark:text-gray-400">Basis</span>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php if($call->platform_profit > 0): ?>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded mr-1"></div>
                                <span class="text-gray-600 dark:text-gray-400">Platform</span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <!--[if BLOCK]><![endif]--><?php if($call->reseller_profit > 0): ?>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded mr-1"></div>
                                <span class="text-gray-600 dark:text-gray-400">Mandant</span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </div>
            </div>
        
        <?php elseif($isReseller && $call->reseller_cost > 0): ?>
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">📈 Ihre Profit-Verteilung</h4>
                <div class="relative">
                    <div class="flex h-8 overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-700">
                        <?php
                            $yourCostPercent = ($call->reseller_cost / $call->customer_cost) * 100;
                            $yourProfitPercent = ($call->reseller_profit / $call->customer_cost) * 100;
                        ?>

                        
                        <div class="bg-gray-500 flex items-center justify-center text-white text-xs font-medium"
                             style="width: <?php echo e($yourCostPercent); ?>%"
                             title="Ihre Kosten: <?php echo e($formatCurrency($call->reseller_cost)); ?>">
                            <!--[if BLOCK]><![endif]--><?php if($yourCostPercent > 15): ?>
                                <?php echo e(round($yourCostPercent)); ?>%
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <!--[if BLOCK]><![endif]--><?php if($call->reseller_profit > 0): ?>
                            <div class="bg-blue-500 flex items-center justify-center text-white text-xs font-medium"
                                 style="width: <?php echo e($yourProfitPercent); ?>%"
                                 title="Ihr Profit: <?php echo e($formatCurrency($call->reseller_profit)); ?>">
                                <!--[if BLOCK]><![endif]--><?php if($yourProfitPercent > 15): ?>
                                    <?php echo e(round($yourProfitPercent)); ?>%
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    
                    <div class="mt-2 flex justify-center space-x-4 text-xs">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-gray-500 rounded mr-1"></div>
                            <span class="text-gray-600 dark:text-gray-400">Meine Kosten</span>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php if($call->reseller_profit > 0): ?>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded mr-1"></div>
                                <span class="text-gray-600 dark:text-gray-400">Mein Profit</span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 sm:p-4">
        <div class="flex items-start gap-2 sm:gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                <div class="flex flex-wrap gap-x-2">
                    <span class="font-semibold">Dauer:</span>
                    <span><?php echo e(gmdate("i:s", $call->duration_sec ?? 0)); ?> Min:Sek</span>
                </div>
                <div class="flex flex-wrap gap-x-2">
                    <span class="font-semibold">Methode:</span>
                    <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-400/10 dark:text-blue-400">
                        <?php echo e($call->total_external_cost_eur_cents > 0 ? 'Tatsächlich' : 'Geschätzt'); ?>

                    </span>
                </div>
                <!--[if BLOCK]><![endif]--><?php if($hasRevenue): ?>
                    <div class="flex flex-wrap gap-x-2">
                        <span class="font-semibold">ROI:</span>
                        <span class="font-mono font-bold text-green-600 dark:text-green-400">
                            <?php echo e($call->base_cost > 0 ? '+' . number_format((($revenue - $call->base_cost) / $call->base_cost) * 100, 0) : 'N/A'); ?>%
                        </span>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <!--[if BLOCK]><![endif]--><?php if($isSuperAdmin): ?>
                    <div class="flex flex-wrap gap-x-2">
                        <span class="font-semibold">Profit-Marge:</span>
                        <span class="font-mono font-bold"><?php echo e($call->profit_margin_total); ?>%</span>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>
    </div>
</div><?php /**PATH /var/www/api-gateway/resources/views/filament/modals/profit-details.blade.php ENDPATH**/ ?>