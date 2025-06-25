# Retell Agent Import/Export/Duplicate Implementation Plan

## Current State Analysis

### What We Already Have

1. **Basic Agent CRUD Operations** (in `RetellV2Service`):
   - `createAgent()` - Create new agents
   - `updateAgent()` - Update existing agents  
   - `getAgent()` - Get single agent details
   - `listAgents()` - List all agents
   - `deleteAgent()` - Delete agents

2. **LLM Management**:
   - `getRetellLLM()` - Get LLM configuration with functions
   - `updateRetellLLM()` - Update LLM configuration

3. **UI Components**:
   - RetellUltimateControlCenter - Main control interface
   - Agent cards with version display
   - Function viewer modal

### What's Missing

Based on the Retell agent export structure analysis, we need to implement:

## 1. Agent Import/Export Service Methods

### Add to `RetellV2Service.php`:

```php
/**
 * Export agent configuration
 * Returns complete agent data including LLM config and functions
 */
public function exportAgent(string $agentId): ?array
{
    $agent = $this->getAgent($agentId);
    if (!$agent) return null;
    
    // Get LLM configuration if using retell-llm
    if ($agent['response_engine']['type'] === 'retell-llm' && 
        isset($agent['response_engine']['llm_id'])) {
        $llmData = $this->getRetellLLM($agent['response_engine']['llm_id']);
        $agent['llm_configuration'] = $llmData;
    }
    
    // Add export metadata
    $agent['export_metadata'] = [
        'exported_at' => now()->toIso8601String(),
        'exported_by' => auth()->user()->email ?? 'system',
        'export_version' => '1.0'
    ];
    
    return $agent;
}

/**
 * Import agent from configuration
 * Creates new agent with provided configuration
 */
public function importAgent(array $agentData, array $options = []): ?array
{
    // Remove IDs and timestamps for new agent
    unset($agentData['agent_id']);
    unset($agentData['last_modification_timestamp']);
    unset($agentData['export_metadata']);
    
    // Override name if provided
    if (isset($options['name'])) {
        $agentData['agent_name'] = $options['name'];
    }
    
    // Create LLM first if needed
    if (isset($agentData['llm_configuration'])) {
        $llmConfig = $agentData['llm_configuration'];
        unset($llmConfig['llm_id']);
        
        $newLlm = $this->createRetellLLM($llmConfig);
        if ($newLlm && isset($newLlm['llm_id'])) {
            $agentData['response_engine']['llm_id'] = $newLlm['llm_id'];
        }
        
        unset($agentData['llm_configuration']);
    }
    
    return $this->createAgent($agentData);
}

/**
 * Duplicate existing agent
 * Creates new version or completely new agent
 */
public function duplicateAgent(string $agentId, array $options = []): ?array
{
    $agentData = $this->exportAgent($agentId);
    if (!$agentData) return null;
    
    // Determine duplication type
    $duplicateType = $options['type'] ?? 'new_version';
    
    if ($duplicateType === 'new_version') {
        // Create new version of same agent
        $agentData['version'] = ($agentData['version'] ?? 0) + 1;
        $agentData['agent_name'] = preg_replace('/\s+V\d+$/', '', $agentData['agent_name']) . ' V' . $agentData['version'];
    } else {
        // Create completely new agent
        $agentData['agent_name'] = ($options['name'] ?? $agentData['agent_name']) . ' (Copy)';
        $agentData['version'] = 0;
    }
    
    return $this->importAgent($agentData, $options);
}

/**
 * Create new Retell LLM
 */
public function createRetellLLM(array $config): ?array
{
    $response = $this->httpWithRetry()
        ->withToken($this->token)
        ->post($this->url . '/create-retell-llm', $config);
        
    if ($response->successful()) {
        return $response->json();
    }
    
    return null;
}
```

## 2. Database Schema Updates

### Create Migration for Agent Templates:

```php
Schema::create('retell_agent_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->json('configuration'); // Full agent export data
    $table->string('category')->nullable(); // e.g., 'appointment', 'support', 'sales'
    $table->boolean('is_public')->default(false); // Share across companies
    $table->integer('usage_count')->default(0);
    $table->timestamps();
    
    $table->index(['company_id', 'category']);
});
```

### Create Migration for Agent Version History:

```php
Schema::create('retell_agent_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('agent_id');
    $table->string('agent_name');
    $table->integer('version');
    $table->json('configuration'); // Full agent config at this version
    $table->string('created_by')->nullable();
    $table->text('change_notes')->nullable();
    $table->boolean('is_active')->default(false);
    $table->timestamps();
    
    $table->unique(['company_id', 'agent_id', 'version']);
    $table->index(['company_id', 'agent_id']);
});
```

## 3. UI Implementation in RetellUltimateControlCenter

### Add Import/Export/Duplicate Buttons to Agent Cards:

```php
// In RetellUltimateControlCenter.php

public function exportAgentConfig(string $agentId): void
{
    try {
        $retellService = $this->getRetellService();
        $agentData = $retellService->exportAgent($agentId);
        
        if ($agentData) {
            // Store in session for download
            session(['agent_export' => $agentData]);
            
            // Trigger file download
            $this->dispatch('download-agent-config', [
                'filename' => Str::slug($agentData['agent_name']) . '-export.json',
                'data' => json_encode($agentData, JSON_PRETTY_PRINT)
            ]);
            
            $this->successMessage = 'Agent configuration exported successfully';
        }
    } catch (\Exception $e) {
        $this->error = 'Failed to export agent: ' . $e->getMessage();
    }
}

public function importAgentConfig(): void
{
    $this->showImportModal = true;
}

public function duplicateAgent(string $agentId, string $type = 'new_version'): void
{
    try {
        $retellService = $this->getRetellService();
        $newAgent = $retellService->duplicateAgent($agentId, [
            'type' => $type,
            'name' => $this->duplicateAgentName ?? null
        ]);
        
        if ($newAgent) {
            $this->loadAgents(); // Refresh list
            $this->successMessage = $type === 'new_version' 
                ? 'New version created successfully'
                : 'Agent duplicated successfully';
        }
    } catch (\Exception $e) {
        $this->error = 'Failed to duplicate agent: ' . $e->getMessage();
    }
}

public function saveAsTemplate(string $agentId): void
{
    try {
        $retellService = $this->getRetellService();
        $agentData = $retellService->exportAgent($agentId);
        
        if ($agentData) {
            \App\Models\RetellAgentTemplate::create([
                'company_id' => $this->companyId,
                'name' => $this->templateName ?? $agentData['agent_name'] . ' Template',
                'description' => $this->templateDescription,
                'configuration' => $agentData,
                'category' => $this->templateCategory ?? 'general'
            ]);
            
            $this->successMessage = 'Agent saved as template successfully';
        }
    } catch (\Exception $e) {
        $this->error = 'Failed to save template: ' . $e->getMessage();
    }
}
```

### Add Import Modal Component:

```blade
{{-- In retell-ultimate-control-center.blade.php --}}
@if($showImportModal)
<div class="import-modal">
    <div class="modal-content">
        <h3>Import Agent Configuration</h3>
        
        <div class="import-options">
            <label>
                <input type="radio" wire:model="importType" value="file">
                Import from JSON file
            </label>
            
            <label>
                <input type="radio" wire:model="importType" value="template">
                Import from template
            </label>
            
            <label>
                <input type="radio" wire:model="importType" value="paste">
                Paste JSON configuration
            </label>
        </div>
        
        @if($importType === 'file')
            <input type="file" 
                   wire:model="importFile" 
                   accept=".json"
                   x-on:change="$wire.processImportFile($event.target.files[0])">
        @endif
        
        @if($importType === 'template')
            <select wire:model="selectedTemplateId">
                <option value="">Select a template...</option>
                @foreach($availableTemplates as $template)
                    <option value="{{ $template->id }}">
                        {{ $template->name }} ({{ $template->category }})
                    </option>
                @endforeach
            </select>
        @endif
        
        @if($importType === 'paste')
            <textarea 
                wire:model="importJson" 
                placeholder="Paste agent JSON configuration here..."
                rows="10">
            </textarea>
        @endif
        
        <div class="import-actions">
            <button wire:click="executeImport" class="btn-primary">
                Import Agent
            </button>
            <button wire:click="$set('showImportModal', false)" class="btn-secondary">
                Cancel
            </button>
        </div>
    </div>
</div>
@endif
```

## 4. Advanced Features to Implement

### Version Comparison:
```php
public function compareAgentVersions(string $agentId, int $version1, int $version2): array
{
    $v1 = RetellAgentVersion::where('agent_id', $agentId)
        ->where('version', $version1)
        ->first();
        
    $v2 = RetellAgentVersion::where('agent_id', $agentId)
        ->where('version', $version2)
        ->first();
        
    // Use diff library to show changes
    $diff = new \Diff($v1->configuration, $v2->configuration);
    
    return [
        'version1' => $v1,
        'version2' => $v2,
        'differences' => $diff->render()
    ];
}
```

### Bulk Operations:
```php
public function bulkExportAgents(array $agentIds): array
{
    $exports = [];
    foreach ($agentIds as $agentId) {
        $exports[] = $this->exportAgent($agentId);
    }
    return $exports;
}

public function bulkImportAgents(array $agentConfigs): array
{
    $results = [];
    foreach ($agentConfigs as $config) {
        $results[] = $this->importAgent($config);
    }
    return $results;
}
```

### Template Marketplace:
```php
// Share templates across companies
public function publishTemplate(int $templateId): void
{
    $template = RetellAgentTemplate::findOrFail($templateId);
    $template->update(['is_public' => true]);
}

public function getMarketplaceTemplates(): Collection
{
    return RetellAgentTemplate::where('is_public', true)
        ->withCount('usages')
        ->orderBy('usage_count', 'desc')
        ->get();
}
```

## 5. Security Considerations

1. **API Key Handling**:
   - Strip API keys from exports
   - Require re-entry on import
   - Validate API keys before use

2. **Permission Checks**:
   - Only allow import/export for authorized users
   - Validate company ownership of agents
   - Sanitize imported data

3. **Rate Limiting**:
   - Limit bulk operations
   - Throttle API calls to Retell

## 6. Next Steps

1. **Phase 1**: Implement basic export/import in RetellV2Service
2. **Phase 2**: Add UI buttons and modals to Control Center
3. **Phase 3**: Create database tables for templates and versions
4. **Phase 4**: Implement advanced features (comparison, bulk ops)
5. **Phase 5**: Add template marketplace functionality

## 7. Testing Requirements

- Unit tests for all new service methods
- Integration tests for import/export flow
- UI tests for modal interactions
- Security tests for data sanitization
- Performance tests for bulk operations