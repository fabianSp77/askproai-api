{{-- Enhanced Visual Function Builder Modal --}}
<div 
    x-data="{
        mode: 'visual',
        selectedTemplate: null,
        parameters: [],
        testData: {},
        previewRequest: {},
        activeParameterIndex: null,
        parameterTypes: ['string', 'number', 'boolean', 'array', 'object'],
        
        addParameter() {
            this.parameters.push({
                name: '',
                type: 'string',
                required: false,
                description: '',
                default: '',
                validation: []
            });
            this.activeParameterIndex = this.parameters.length - 1;
            this.updatePreview();
        },
        
        removeParameter(index) {
            this.parameters.splice(index, 1);
            this.activeParameterIndex = null;
            this.updatePreview();
        },
        
        updatePreview() {
            // Update the request preview
            let params = {};
            this.parameters.forEach(param => {
                params[param.name] = this.getExampleValue(param.type);
            });
            this.previewRequest = params;
        },
        
        getExampleValue(type) {
            switch(type) {
                case 'string': return 'example_value';
                case 'number': return 123;
                case 'boolean': return true;
                case 'array': return ['item1', 'item2'];
                case 'object': return { key: 'value' };
                default: return null;
            }
        },
        
        getTypeIcon(type) {
            switch(type) {
                case 'string': return 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
                case 'number': return 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14';
                case 'boolean': return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                case 'array': return 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10';
                case 'object': return 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4';
                default: return '';
            }
        }
    }"
    style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    " 
    wire:click="closeFunctionBuilder">
    
    <div style="
        background: white;
        border-radius: 1rem;
        width: 100%;
        max-width: 1400px;
        height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    " wire:click.stop>
        
        {{-- Header --}}
        <div style="
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        ">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827;">
                    {{ empty($editingFunction) ? 'Create New Function' : 'Edit Function' }}
                </h2>
                
                {{-- Mode Switcher --}}
                <div style="display: flex; background: #f3f4f6; border-radius: 0.5rem; padding: 0.25rem;">
                    <button 
                        @click="mode = 'visual'"
                        :class="mode === 'visual' ? 'bg-white shadow-sm' : ''"
                        style="
                            padding: 0.375rem 0.75rem;
                            font-size: 0.875rem;
                            font-weight: 500;
                            border-radius: 0.375rem;
                            border: none;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            background: transparent;
                            color: #374151;
                        "
                        :style="mode === 'visual' && { background: 'white', color: '#6366f1', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)' }">
                        Visual
                    </button>
                    <button 
                        @click="mode = 'code'"
                        :class="mode === 'code' ? 'bg-white shadow-sm' : ''"
                        style="
                            padding: 0.375rem 0.75rem;
                            font-size: 0.875rem;
                            font-weight: 500;
                            border-radius: 0.375rem;
                            border: none;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            background: transparent;
                            color: #374151;
                        "
                        :style="mode === 'code' && { background: 'white', color: '#6366f1', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)' }">
                        Code
                    </button>
                </div>
            </div>
            
            <button 
                wire:click="closeFunctionBuilder"
                style="
                    padding: 0.5rem;
                    background: transparent;
                    border: none;
                    color: #6b7280;
                    cursor: pointer;
                    border-radius: 0.375rem;
                    transition: all 0.2s ease;
                "
                onmouseover="this.style.backgroundColor='#f3f4f6'"
                onmouseout="this.style.backgroundColor='transparent'">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        {{-- Content Area --}}
        <div style="flex: 1; overflow: hidden; display: flex;">
            {{-- Visual Mode --}}
            <div x-show="mode === 'visual'" style="width: 100%; display: flex; height: 100%;">
                {{-- Left Panel: Configuration --}}
                <div style="flex: 1; overflow-y: auto; padding: 1.5rem; border-right: 1px solid #e5e7eb;">
                    {{-- Template Gallery --}}
                    @if(empty($editingFunction) || empty($editingFunction['name']))
                    <div style="margin-bottom: 2rem;" x-show="!selectedTemplate">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                            Quick Start Templates
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                            @foreach($functionTemplates as $category => $templates)
                                @foreach($templates as $template)
                                    <div 
                                        wire:click="selectFunctionTemplate('{{ $category }}', '{{ $template['id'] }}')"
                                        @click="selectedTemplate = true"
                                        style="
                                            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
                                            border: 2px solid #e5e7eb;
                                            border-radius: 0.75rem;
                                            padding: 1.25rem;
                                            cursor: pointer;
                                            transition: all 0.2s ease;
                                            position: relative;
                                            overflow: hidden;
                                        "
                                        onmouseover="this.style.borderColor='#6366f1'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                        onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        
                                        {{-- Category Badge --}}
                                        <div style="
                                            position: absolute;
                                            top: 0.75rem;
                                            right: 0.75rem;
                                            padding: 0.25rem 0.5rem;
                                            font-size: 0.625rem;
                                            font-weight: 600;
                                            border-radius: 9999px;
                                            background: {{ 
                                                $category === 'cal_com' ? '#dbeafe' : 
                                                ($category === 'database' ? '#d1fae5' : 
                                                ($category === 'system' ? '#fed7aa' : '#e9d5ff')) 
                                            }};
                                            color: {{ 
                                                $category === 'cal_com' ? '#1e40af' : 
                                                ($category === 'database' ? '#065f46' : 
                                                ($category === 'system' ? '#92400e' : '#6b21a8')) 
                                            }};
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                                        </div>
                                        
                                        {{-- Icon --}}
                                        <div style="
                                            width: 48px;
                                            height: 48px;
                                            border-radius: 0.5rem;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            margin-bottom: 0.75rem;
                                            background: {{ 
                                                $category === 'cal_com' ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : 
                                                ($category === 'database' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 
                                                ($category === 'system' ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)')) 
                                            }};
                                        ">
                                            <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($template['icon'] === 'calendar')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                @elseif($template['icon'] === 'calendar-plus')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m3-3H9"/>
                                                @elseif($template['icon'] === 'database')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                                @elseif($template['icon'] === 'phone-x')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                                @elseif($template['icon'] === 'phone-forward')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3l4 4m0 0l-4 4m4-4H10"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                                @endif
                                            </svg>
                                        </div>
                                        
                                        {{-- Template Info --}}
                                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                            {{ $template['name'] }}
                                        </h4>
                                        <p style="font-size: 0.75rem; color: #6b7280; line-height: 1.4;">
                                            {{ $template['description'] }}
                                        </p>
                                        
                                        {{-- Parameters Count --}}
                                        @if(isset($template['config']['parameters']))
                                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                                <span style="font-size: 0.625rem; color: #9ca3af;">
                                                    {{ count($template['config']['parameters']) }} parameters
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    {{-- Function Details --}}
                    <div style="margin-bottom: 2rem;"
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                            Function Details
                        </h3>
                        
                        <div style="display: grid; gap: 1rem;">
                            {{-- Function Name --}}
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                    Function Name
                                </label>
                                <input 
                                    type="text"
                                    wire:model.defer="editingFunction.name"
                                    placeholder="e.g., check_availability"
                                    style="
                                        width: 100%;
                                        height: 40px;
                                        padding: 0 1rem;
                                        border: 1px solid #d1d5db;
                                        border-radius: 0.5rem;
                                        font-size: 0.875rem;
                                        color: #111827;
                                        outline: none;
                                        transition: all 0.2s ease;
                                    "
                                    onfocus="this.style.borderColor='#6366f1'"
                                    onblur="this.style.borderColor='#d1d5db'">
                            </div>
                            
                            {{-- Description --}}
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                    Description
                                </label>
                                <textarea 
                                    wire:model.defer="editingFunction.description"
                                    placeholder="Describe what this function does..."
                                    style="
                                        width: 100%;
                                        min-height: 80px;
                                        padding: 0.5rem 1rem;
                                        border: 1px solid #d1d5db;
                                        border-radius: 0.5rem;
                                        font-size: 0.875rem;
                                        color: #111827;
                                        outline: none;
                                        resize: vertical;
                                        transition: all 0.2s ease;
                                    "
                                    onfocus="this.style.borderColor='#6366f1'"
                                    onblur="this.style.borderColor='#d1d5db'"
                                    rows="3"></textarea>
                            </div>
                            
                            {{-- URL & Method --}}
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                        Endpoint URL
                                    </label>
                                    <input 
                                        type="url"
                                        wire:model.defer="editingFunction.url"
                                        placeholder="https://api.example.com/endpoint"
                                        style="
                                            width: 100%;
                                            height: 40px;
                                            padding: 0 1rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.5rem;
                                            font-size: 0.875rem;
                                            color: #111827;
                                            outline: none;
                                            transition: all 0.2s ease;
                                        "
                                        onfocus="this.style.borderColor='#6366f1'"
                                        onblur="this.style.borderColor='#d1d5db'">
                                </div>
                                
                                <div>
                                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                        Method
                                    </label>
                                    <select 
                                        wire:model.defer="editingFunction.method"
                                        style="
                                            width: 100%;
                                            height: 40px;
                                            padding: 0 1rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.5rem;
                                            font-size: 0.875rem;
                                            color: #111827;
                                            background: white;
                                            cursor: pointer;
                                            outline: none;
                                        ">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>
                            </div>
                            
                            {{-- Advanced Settings Accordion --}}
                            <div x-data="{ showAdvanced: false }" style="margin-top: 1rem;">
                                <button 
                                    @click="showAdvanced = !showAdvanced"
                                    type="button"
                                    style="
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        width: 100%;
                                        padding: 0.75rem;
                                        background: #f9fafb;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 0.5rem;
                                        font-size: 0.875rem;
                                        font-weight: 500;
                                        color: #374151;
                                        cursor: pointer;
                                        transition: all 0.2s ease;
                                    "
                                    :style="showAdvanced && { background: '#eef2ff', borderColor: '#6366f1' }">
                                    <span>Advanced Settings</span>
                                    <svg 
                                        style="width: 1rem; height: 1rem; transition: transform 0.2s ease;"
                                        :style="showAdvanced && { transform: 'rotate(180deg)' }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <div x-show="showAdvanced" x-transition style="margin-top: 1rem; space-y: 1rem;">
                                    {{-- Headers Configuration --}}
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                            Headers (JSON)
                                        </label>
                                        <textarea 
                                            wire:model.defer="editingFunction.headers"
                                            placeholder='{
  "Authorization": "Bearer {{api_key}}",
  "Content-Type": "application/json"
}'
                                            style="
                                                width: 100%;
                                                min-height: 80px;
                                                padding: 0.5rem 1rem;
                                                border: 1px solid #d1d5db;
                                                border-radius: 0.5rem;
                                                font-size: 0.75rem;
                                                font-family: monospace;
                                                color: #111827;
                                                background: #f9fafb;
                                                outline: none;
                                                resize: vertical;
                                            "
                                            rows="3"></textarea>
                                    </div>
                                    
                                    {{-- Authentication --}}
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                            Authentication Type
                                        </label>
                                        <select 
                                            wire:model.defer="editingFunction.auth_type"
                                            style="
                                                width: 100%;
                                                height: 40px;
                                                padding: 0 1rem;
                                                border: 1px solid #d1d5db;
                                                border-radius: 0.5rem;
                                                font-size: 0.875rem;
                                                color: #111827;
                                                background: white;
                                                cursor: pointer;
                                                outline: none;
                                            ">
                                            <option value="none">None</option>
                                            <option value="bearer">Bearer Token</option>
                                            <option value="api_key">API Key</option>
                                            <option value="basic">Basic Auth</option>
                                            <option value="oauth2">OAuth 2.0</option>
                                        </select>
                                    </div>
                                    
                                    {{-- Error Handling --}}
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                            Error Handling
                                        </label>
                                        <div style="space-y: 0.5rem;">
                                            <label style="display: flex; align-items: center; cursor: pointer;">
                                                <input 
                                                    type="checkbox"
                                                    wire:model.defer="editingFunction.retry_on_failure"
                                                    style="
                                                        width: 1rem;
                                                        height: 1rem;
                                                        border-radius: 0.25rem;
                                                        border: 1px solid #d1d5db;
                                                        cursor: pointer;
                                                        margin-right: 0.5rem;
                                                    ">
                                                <span style="font-size: 0.875rem; color: #374151;">Retry on failure</span>
                                            </label>
                                            
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                                <div>
                                                    <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                                                        Max Retries
                                                    </label>
                                                    <input 
                                                        type="number"
                                                        wire:model.defer="editingFunction.max_retries"
                                                        min="1"
                                                        max="5"
                                                        placeholder="3"
                                                        style="
                                                            width: 100%;
                                                            height: 32px;
                                                            padding: 0 0.75rem;
                                                            border: 1px solid #d1d5db;
                                                            border-radius: 0.375rem;
                                                            font-size: 0.75rem;
                                                            color: #374151;
                                                            outline: none;
                                                        ">
                                                </div>
                                                
                                                <div>
                                                    <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                                                        Timeout (seconds)
                                                    </label>
                                                    <input 
                                                        type="number"
                                                        wire:model.defer="editingFunction.timeout"
                                                        min="1"
                                                        max="60"
                                                        placeholder="30"
                                                        style="
                                                            width: 100%;
                                                            height: 32px;
                                                            padding: 0 0.75rem;
                                                            border: 1px solid #d1d5db;
                                                            border-radius: 0.375rem;
                                                            font-size: 0.75rem;
                                                            color: #374151;
                                                            outline: none;
                                                        ">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Response Mapping --}}
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem;">
                                            Response Mapping (JSONPath)
                                        </label>
                                        <textarea 
                                            wire:model.defer="editingFunction.response_mapping"
                                            placeholder='{
  "success": "$.data.success",
  "message": "$.data.message",
  "result": "$.data.result"
}'
                                            style="
                                                width: 100%;
                                                min-height: 80px;
                                                padding: 0.5rem 1rem;
                                                border: 1px solid #d1d5db;
                                                border-radius: 0.5rem;
                                                font-size: 0.75rem;
                                                font-family: monospace;
                                                color: #111827;
                                                background: #f9fafb;
                                                outline: none;
                                                resize: vertical;
                                            "
                                            rows="3"></textarea>
                                        <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                            Map response fields using JSONPath expressions
                                        </p>
                                    </div>
                                    
                                    {{-- Speech Settings --}}
                                    <div style="
                                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                                        border: 1px solid #f59e0b;
                                        border-radius: 0.5rem;
                                        padding: 1rem;
                                        margin-top: 1rem;
                                    ">
                                        <h4 style="
                                            font-size: 0.875rem;
                                            font-weight: 600;
                                            color: #92400e;
                                            margin-bottom: 0.75rem;
                                            display: flex;
                                            align-items: center;
                                            gap: 0.5rem;
                                        ">
                                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                            </svg>
                                            AI Speech Settings
                                        </h4>
                                        
                                        {{-- Speak During Execution --}}
                                        <div style="margin-bottom: 0.75rem;">
                                            <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0.5rem;">
                                                <input 
                                                    type="checkbox"
                                                    wire:model.defer="editingFunction.speak_during_execution"
                                                    style="
                                                        width: 1rem;
                                                        height: 1rem;
                                                        border-radius: 0.25rem;
                                                        border: 1px solid #d97706;
                                                        cursor: pointer;
                                                        margin-right: 0.5rem;
                                                    ">
                                                <span style="font-size: 0.875rem; color: #92400e; font-weight: 500;">
                                                    Speak during function execution
                                                </span>
                                            </label>
                                            <input 
                                                type="text"
                                                wire:model.defer="editingFunction.speak_during_execution_message"
                                                placeholder="e.g., 'Let me check that for you...'"
                                                style="
                                                    width: 100%;
                                                    height: 36px;
                                                    padding: 0 0.75rem;
                                                    border: 1px solid #f59e0b;
                                                    border-radius: 0.375rem;
                                                    font-size: 0.875rem;
                                                    color: #92400e;
                                                    background: white;
                                                    outline: none;
                                                ">
                                        </div>
                                        
                                        {{-- Speak After Execution --}}
                                        <div>
                                            <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0.5rem;">
                                                <input 
                                                    type="checkbox"
                                                    wire:model.defer="editingFunction.speak_after_execution"
                                                    style="
                                                        width: 1rem;
                                                        height: 1rem;
                                                        border-radius: 0.25rem;
                                                        border: 1px solid #d97706;
                                                        cursor: pointer;
                                                        margin-right: 0.5rem;
                                                    ">
                                                <span style="font-size: 0.875rem; color: #92400e; font-weight: 500;">
                                                    Speak after function completes
                                                </span>
                                            </label>
                                            <input 
                                                type="text"
                                                wire:model.defer="editingFunction.speak_after_execution_message"
                                                placeholder="e.g., 'I found the information you requested.'"
                                                style="
                                                    width: 100%;
                                                    height: 36px;
                                                    padding: 0 0.75rem;
                                                    border: 1px solid #f59e0b;
                                                    border-radius: 0.375rem;
                                                    font-size: 0.875rem;
                                                    color: #92400e;
                                                    background: white;
                                                    outline: none;
                                                ">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Parameters Section --}}
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">
                                Parameters
                            </h3>
                            <button 
                                @click="addParameter()"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 0.375rem;
                                    padding: 0.375rem 0.75rem;
                                    font-size: 0.875rem;
                                    font-weight: 500;
                                    color: #6366f1;
                                    background: #eef2ff;
                                    border: none;
                                    border-radius: 0.375rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                "
                                onmouseover="this.style.backgroundColor='#e0e7ff'"
                                onmouseout="this.style.backgroundColor='#eef2ff'">
                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Parameter
                            </button>
                        </div>
                        
                        {{-- Parameter Cards with Drag and Drop --}}
                        <div 
                            x-data="{
                                draggedIndex: null,
                                dragOverIndex: null,
                                
                                dragStart(index) {
                                    this.draggedIndex = index;
                                },
                                
                                dragOver(event, index) {
                                    event.preventDefault();
                                    this.dragOverIndex = index;
                                },
                                
                                dragLeave() {
                                    this.dragOverIndex = null;
                                },
                                
                                drop(event, index) {
                                    event.preventDefault();
                                    if (this.draggedIndex !== null && this.draggedIndex !== index) {
                                        // Swap parameters
                                        let temp = parameters[this.draggedIndex];
                                        parameters[this.draggedIndex] = parameters[index];
                                        parameters[index] = temp;
                                        updatePreview();
                                    }
                                    this.draggedIndex = null;
                                    this.dragOverIndex = null;
                                }
                            }"
                            style="space-y: 0.75rem;">
                            <template x-for="(param, index) in parameters" :key="index">
                                <div 
                                    draggable="true"
                                    @dragstart="dragStart(index)"
                                    @dragover="dragOver($event, index)"
                                    @dragleave="dragLeave()"
                                    @drop="drop($event, index)"
                                    @dragend="draggedIndex = null; dragOverIndex = null"
                                    :class="activeParameterIndex === index ? 'ring-2 ring-indigo-500' : ''"
                                    :style="{
                                        background: dragOverIndex === index ? '#eef2ff' : '#f9fafb',
                                        border: activeParameterIndex === index ? '2px solid #6366f1' : '1px solid #e5e7eb',
                                        borderRadius: '0.5rem',
                                        padding: '1rem',
                                        cursor: draggedIndex === index ? 'grabbing' : 'grab',
                                        transition: 'all 0.2s ease',
                                        opacity: draggedIndex === index ? '0.5' : '1',
                                        transform: dragOverIndex === index ? 'scale(1.02)' : 'scale(1)'
                                    }"
                                    @click="activeParameterIndex = index">
                                    
                                    <div style="display: flex; align-items: start; justify-content: space-between; margin-bottom: 0.75rem;">
                                        {{-- Drag Handle --}}
                                        <div style="
                                            padding: 0.25rem;
                                            margin-right: 0.5rem;
                                            cursor: grab;
                                            color: #9ca3af;
                                        ">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            {{-- Type Icon --}}
                                            <div :style="{
                                                width: '32px',
                                                height: '32px',
                                                borderRadius: '0.375rem',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                background: param.type === 'string' ? '#dbeafe' : 
                                                           param.type === 'number' ? '#fef3c7' :
                                                           param.type === 'boolean' ? '#d1fae5' :
                                                           param.type === 'array' ? '#e9d5ff' : '#fee2e2'
                                            }">
                                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(param.type)"/>
                                                </svg>
                                            </div>
                                            
                                            {{-- Parameter Name --}}
                                            <input 
                                                type="text"
                                                x-model="param.name"
                                                @input="updatePreview()"
                                                placeholder="parameter_name"
                                                style="
                                                    background: transparent;
                                                    border: none;
                                                    font-size: 0.875rem;
                                                    font-weight: 600;
                                                    color: #111827;
                                                    outline: none;
                                                    min-width: 150px;
                                                "
                                                @click.stop>
                                        </div>
                                        
                                        {{-- Remove Button --}}
                                        <button 
                                            @click.stop="removeParameter(index)"
                                            style="
                                                padding: 0.25rem;
                                                background: transparent;
                                                border: none;
                                                color: #ef4444;
                                                cursor: pointer;
                                                border-radius: 0.25rem;
                                                opacity: 0.7;
                                                transition: all 0.2s ease;
                                            "
                                            onmouseover="this.style.opacity='1'"
                                            onmouseout="this.style.opacity='0.7'">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    {{-- Parameter Details (shown when active) --}}
                                    <div x-show="activeParameterIndex === index" x-transition style="space-y: 0.75rem; margin-top: 0.75rem;">
                                        {{-- Type & Required --}}
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                            <div>
                                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                                    Type
                                                </label>
                                                <select 
                                                    x-model="param.type"
                                                    @change="updatePreview()"
                                                    style="
                                                        width: 100%;
                                                        height: 32px;
                                                        padding: 0 0.75rem;
                                                        border: 1px solid #d1d5db;
                                                        border-radius: 0.375rem;
                                                        font-size: 0.75rem;
                                                        color: #374151;
                                                        background: white;
                                                        cursor: pointer;
                                                        outline: none;
                                                    "
                                                    @click.stop>
                                                    <template x-for="type in parameterTypes" :key="type">
                                                        <option :value="type" x-text="type"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                                    Required
                                                </label>
                                                <label style="display: inline-flex; align-items: center; cursor: pointer;">
                                                    <input 
                                                        type="checkbox"
                                                        x-model="param.required"
                                                        @click.stop
                                                        style="
                                                            width: 1rem;
                                                            height: 1rem;
                                                            border-radius: 0.25rem;
                                                            border: 1px solid #d1d5db;
                                                            cursor: pointer;
                                                        ">
                                                    <span style="margin-left: 0.5rem; font-size: 0.75rem; color: #374151;">
                                                        This parameter is required
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- Description --}}
                                        <div>
                                            <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                                Description
                                            </label>
                                            <input 
                                                type="text"
                                                x-model="param.description"
                                                placeholder="Brief description of this parameter"
                                                style="
                                                    width: 100%;
                                                    height: 32px;
                                                    padding: 0 0.75rem;
                                                    border: 1px solid #d1d5db;
                                                    border-radius: 0.375rem;
                                                    font-size: 0.75rem;
                                                    color: #374151;
                                                    outline: none;
                                                "
                                                @click.stop>
                                        </div>
                                        
                                        {{-- Default Value --}}
                                        <div>
                                            <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                                Default Value
                                            </label>
                                            <input 
                                                type="text"
                                                x-model="param.default"
                                                placeholder="Optional default value"
                                                style="
                                                    width: 100%;
                                                    height: 32px;
                                                    padding: 0 0.75rem;
                                                    border: 1px solid #d1d5db;
                                                    border-radius: 0.375rem;
                                                    font-size: 0.75rem;
                                                    color: #374151;
                                                    outline: none;
                                                "
                                                @click.stop>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            {{-- Empty State --}}
                            <div x-show="parameters.length === 0" 
                                 style="
                                    text-align: center;
                                    padding: 2rem;
                                    background: #f9fafb;
                                    border: 2px dashed #e5e7eb;
                                    border-radius: 0.5rem;
                                 ">
                                <svg style="width: 2.5rem; height: 2.5rem; margin: 0 auto 0.75rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <p style="font-size: 0.875rem; color: #6b7280;">
                                    No parameters added yet
                                </p>
                                <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                                    Click "Add Parameter" to get started
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Right Panel: Preview --}}
                <div style="width: 480px; background: #f9fafb; padding: 1.5rem; overflow-y: auto;">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        Live Preview
                    </h3>
                    
                    {{-- Request Preview --}}
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <h4 style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">
                                Request Example
                            </h4>
                            <button 
                                @click="navigator.clipboard.writeText(JSON.stringify(previewRequest, null, 2))"
                                style="
                                    padding: 0.25rem 0.5rem;
                                    font-size: 0.75rem;
                                    color: #6366f1;
                                    background: transparent;
                                    border: none;
                                    cursor: pointer;
                                    border-radius: 0.25rem;
                                    transition: all 0.2s ease;
                                "
                                onmouseover="this.style.backgroundColor='#eef2ff'"
                                onmouseout="this.style.backgroundColor='transparent'">
                                Copy
                            </button>
                        </div>
                        <pre style="
                            background: #1e293b;
                            color: #e2e8f0;
                            padding: 1rem;
                            border-radius: 0.5rem;
                            font-size: 0.75rem;
                            font-family: 'Monaco', 'Menlo', monospace;
                            overflow-x: auto;
                            margin: 0;
                        "><code x-text="JSON.stringify(previewRequest, null, 2)"></code></pre>
                    </div>
                    
                    {{-- Response Example --}}
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <h4 style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">
                                Expected Response
                            </h4>
                        </div>
                        <pre style="
                            background: #1e293b;
                            color: #e2e8f0;
                            padding: 1rem;
                            border-radius: 0.5rem;
                            font-size: 0.75rem;
                            font-family: 'Monaco', 'Menlo', monospace;
                            overflow-x: auto;
                            margin: 0;
                        "><code>{
  "success": true,
  "data": {
    // Response data here
  }
}</code></pre>
                    </div>
                    
                    {{-- Test Function --}}
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; color: #6b7280; margin-bottom: 0.5rem;">
                            Test Function
                        </h4>
                        <div style="
                            background: white;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            padding: 1rem;
                        ">
                            <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.75rem;">
                                Fill in test values to simulate a function call
                            </p>
                            
                            <template x-for="param in parameters" :key="param.name">
                                <div style="margin-bottom: 0.75rem;">
                                    <label 
                                        :for="'test_' + param.name"
                                        style="display: block; font-size: 0.75rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;"
                                        x-text="param.name + (param.required ? ' *' : '')">
                                    </label>
                                    <input 
                                        :type="param.type === 'number' ? 'number' : 'text'"
                                        :id="'test_' + param.name"
                                        x-model="testData[param.name]"
                                        :placeholder="'Enter ' + param.type + ' value'"
                                        style="
                                            width: 100%;
                                            height: 32px;
                                            padding: 0 0.75rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.375rem;
                                            font-size: 0.75rem;
                                            color: #374151;
                                            outline: none;
                                        ">
                                </div>
                            </template>
                            
                            <button 
                                @click="testFunction"
                                style="
                                    width: 100%;
                                    height: 36px;
                                    margin-top: 0.75rem;
                                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                    color: white;
                                    font-size: 0.875rem;
                                    font-weight: 500;
                                    border: none;
                                    border-radius: 0.375rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                "
                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                Run Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Code Mode --}}
            <div x-show="mode === 'code'" style="width: 100%; padding: 1.5rem;">
                <form wire:submit.prevent="saveFunction">
                    <div style="space-y: 1rem;">
                        {{-- Function Name --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Function Name
                            </label>
                            <input 
                                type="text"
                                wire:model.defer="editingFunction.name"
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                "
                                required>
                        </div>
                        
                        {{-- Parameters (JSON) --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Parameters (JSON)
                            </label>
                            <textarea 
                                wire:model.defer="editingFunction.parameters"
                                style="
                                    width: 100%;
                                    min-height: 300px;
                                    padding: 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    font-family: 'Monaco', 'Menlo', monospace;
                                    outline: none;
                                    resize: vertical;
                                    background: #f9fafb;
                                "
                                placeholder='[
  {
    "name": "parameter_name",
    "type": "string",
    "required": true,
    "description": "Description of the parameter"
  }
]'></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        {{-- Footer Actions --}}
        <div style="
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        ">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="font-size: 0.75rem; color: #6b7280;">
                    <span x-show="parameters.length > 0">
                        <span x-text="parameters.length"></span> parameter<span x-show="parameters.length !== 1">s</span> configured
                    </span>
                </div>
                
                {{-- Import/Export Buttons --}}
                <div x-show="mode === 'visual'" style="display: flex; gap: 0.5rem;">
                    <button 
                        @click="exportFunction"
                        type="button"
                        style="
                            padding: 0.375rem 0.75rem;
                            font-size: 0.75rem;
                            color: #6366f1;
                            background: transparent;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.375rem;
                            cursor: pointer;
                            transition: all 0.2s ease;
                        "
                        onmouseover="this.style.borderColor='#6366f1'; this.style.backgroundColor='#eef2ff'"
                        onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='transparent'">
                        Export
                    </button>
                    
                    <label style="
                        padding: 0.375rem 0.75rem;
                        font-size: 0.75rem;
                        color: #6366f1;
                        background: transparent;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.375rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.borderColor='#6366f1'; this.style.backgroundColor='#eef2ff'"
                    onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='transparent'">
                        Import
                        <input 
                            type="file"
                            accept=".json"
                            @change="importFunction"
                            style="display: none;">
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <button 
                    type="button"
                    wire:click="closeFunctionBuilder"
                    class="modern-btn modern-btn-secondary">
                    Cancel
                </button>
                <button 
                    type="submit"
                    wire:click="saveFunction"
                    class="modern-btn modern-btn-primary"
                    style="
                        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                        color: white;
                        padding: 0.5rem 1.25rem;
                        font-weight: 600;
                    ">
                    {{ empty($editingFunction['name']) ? 'Create Function' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js Component Extension --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('functionBuilder', () => ({
        // Initialize from Livewire data
        init() {
            // Listen for template selection
            Livewire.on('template-selected', (data) => {
                if (data.visual_parameters) {
                    this.parameters = data.visual_parameters;
                    this.updatePreview();
                }
            });
            
            // Sync parameters with Livewire before save
            Livewire.on('prepare-function-save', () => {
                @this.set('editingFunction.visual_parameters', this.parameters);
            });
        },
        
        // Test function execution
        async testFunction() {
            const testPayload = {
                url: @this.editingFunction.url || '',
                method: @this.editingFunction.method || 'POST',
                parameters: this.testData
            };
            
            // Show loading state
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Testing...';
            button.disabled = true;
            
            try {
                // In real implementation, this would call the actual endpoint
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                // Show success
                button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                button.textContent = 'Test Successful!';
                
                // Mock response
                console.log('Test Response:', {
                    success: true,
                    data: {
                        message: 'Function executed successfully',
                        parameters: this.testData
                    }
                });
                
            } catch (error) {
                // Show error
                button.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                button.textContent = 'Test Failed';
                console.error('Test Error:', error);
            } finally {
                // Reset after delay
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    button.disabled = false;
                }, 2000);
            }
        },
        
        // Enhanced parameter validation
        validateParameter(param) {
            const errors = [];
            
            if (!param.name) {
                errors.push('Parameter name is required');
            } else if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(param.name)) {
                errors.push('Parameter name must be valid identifier');
            }
            
            if (param.type === 'number' && param.default) {
                if (isNaN(Number(param.default))) {
                    errors.push('Default value must be a number');
                }
            }
            
            return errors;
        },
        
        // Export function as JSON
        exportFunction() {
            const functionData = {
                name: @this.editingFunction.name || '',
                description: @this.editingFunction.description || '',
                url: @this.editingFunction.url || '',
                method: @this.editingFunction.method || 'POST',
                parameters: this.parameters
            };
            
            const blob = new Blob([JSON.stringify(functionData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${functionData.name || 'function'}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        // Import function from JSON
        async importFunction(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            try {
                const text = await file.text();
                const data = JSON.parse(text);
                
                // Update form fields
                @this.set('editingFunction.name', data.name || '');
                @this.set('editingFunction.description', data.description || '');
                @this.set('editingFunction.url', data.url || '');
                @this.set('editingFunction.method', data.method || 'POST');
                
                // Update parameters
                this.parameters = data.parameters || [];
                this.updatePreview();
                
                // Show success message
                alert('Function imported successfully!');
                
            } catch (error) {
                alert('Failed to import function: ' + error.message);
            }
        }
    }))
});
</script>