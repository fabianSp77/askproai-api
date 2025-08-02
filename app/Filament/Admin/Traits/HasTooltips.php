<?php

namespace App\Filament\Admin\Traits;

trait HasTooltips
{
    /**
     * Get tooltip text from language file
     */
    protected static function tooltip(string $key): string
    {
        return __('admin.tooltips.' . $key);
    }
    
    /**
     * Apply common tooltips to standard actions
     */
    protected static function applyCommonTooltips($action, string $type = null)
    {
        // Map common action names to tooltip keys
        $tooltipMap = [
            // CRUD Actions
            'create' => 'create_entry',
            'view' => 'view_details',
            'edit' => 'edit_entry',
            'delete' => 'delete_entry',
            'restore' => 'restore_entry',
            'force_delete' => 'delete_entry',
            
            // Data Actions
            'refresh' => 'refresh_data',
            'refresh_data' => 'refresh_data',
            'sync' => 'sync_data',
            'export' => 'export_csv',
            'import' => 'import_data',
            'download' => 'download_pdf',
            
            // Financial Actions
            'mark_as_non_billable' => 'mark_non_billable',
            'markAsNonBillable' => 'mark_non_billable',
            'create_credit_note' => 'create_credit_note',
            'createCreditNote' => 'create_credit_note',
            'finalize' => 'finalize_invoice',
            'preview' => 'preview_invoice',
            
            // System Actions
            'preflight_check' => 'preflight_check',
            'health_check' => 'health_check',
            'test_connection' => 'test_connection',
            
            // Communication Actions
            'send_email' => 'send_email',
            'send_sms' => 'send_sms',
            'call' => 'call_now',
            
            // Status Actions
            'archive' => 'archive_entry',
            'duplicate' => 'duplicate',
            'approve' => 'approve',
            'reject' => 'reject',
            
            // Bulk Actions
            'bulk_delete' => 'bulk_delete',
            'bulk_export' => 'bulk_export',
            'bulk_edit' => 'bulk_edit',
        ];
        
        $actionName = $action->getName();
        
        // Check if we have a tooltip for this action
        if (isset($tooltipMap[$actionName])) {
            $action->tooltip(static::tooltip($tooltipMap[$actionName]));
        }
        
        // Apply type-specific tooltips
        if ($type) {
            $typeKey = $actionName . '_' . $type;
            if (__('admin.tooltips.' . $typeKey) !== 'admin.tooltips.' . $typeKey) {
                $action->tooltip(static::tooltip($typeKey));
            }
        }
        
        return $action;
    }
    
    /**
     * Apply tooltips to table header actions
     */
    protected static function applyHeaderActionTooltips(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            return static::applyCommonTooltips($action);
        })->toArray();
    }
    
    /**
     * Apply tooltips to table row actions
     */
    protected static function applyTableActionTooltips(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            // Handle action groups
            if ($action instanceof \Filament\Tables\Actions\ActionGroup) {
                $groupActions = $action->getActions();
                $tooltippedActions = collect($groupActions)->map(function ($groupAction) {
                    return static::applyCommonTooltips($groupAction);
                })->toArray();
                
                // Recreate the action group with tooltipped actions
                return $action->actions($tooltippedActions);
            }
            
            return static::applyCommonTooltips($action);
        })->toArray();
    }
    
    /**
     * Apply tooltips to bulk actions
     */
    protected static function applyBulkActionTooltips(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            if ($action instanceof \Filament\Tables\Actions\BulkActionGroup) {
                $groupActions = $action->getActions();
                $tooltippedActions = collect($groupActions)->map(function ($bulkAction) {
                    return static::applyCommonTooltips($bulkAction);
                })->toArray();
                
                return $action->actions($tooltippedActions);
            }
            
            return static::applyCommonTooltips($action);
        })->toArray();
    }
    
    /**
     * Apply tooltips to form actions
     */
    protected static function applyFormActionTooltips(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            return static::applyCommonTooltips($action);
        })->toArray();
    }
}