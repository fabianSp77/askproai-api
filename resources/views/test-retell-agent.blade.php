<!DOCTYPE html>
<html>
<head>
    <title>RetellAgent Test - ID {{ $agent->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .field { margin: 10px 0; }
        .label { font-weight: bold; color: #666; display: inline-block; width: 200px; }
        .value { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .json-display { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>RetellAgent Test View - Direct Data Display</h1>
        
        <div class="section">
            <h2>Basic Information</h2>
            <div class="field">
                <span class="label">ID:</span>
                <span class="value">{{ $agent->id }}</span>
            </div>
            <div class="field">
                <span class="label">Name:</span>
                <span class="value">{{ $agent->name }}</span>
            </div>
            <div class="field">
                <span class="label">Agent ID:</span>
                <span class="value">{{ $agent->agent_id }}</span>
            </div>
            <div class="field">
                <span class="label">Company ID:</span>
                <span class="value">{{ $agent->company_id ?? 'NULL' }}</span>
            </div>
            <div class="field">
                <span class="label">Company Name:</span>
                <span class="value">{{ $agent->company ? $agent->company->name : 'No company' }}</span>
            </div>
        </div>

        <div class="section">
            <h2>Status Information</h2>
            <div class="field">
                <span class="label">Is Active:</span>
                <span class="value {{ $agent->is_active ? 'success' : 'error' }}">
                    {{ $agent->is_active ? 'YES' : 'NO' }}
                </span>
            </div>
            <div class="field">
                <span class="label">Active:</span>
                <span class="value {{ $agent->active ? 'success' : 'error' }}">
                    {{ $agent->active ? 'YES' : 'NO' }}
                </span>
            </div>
            <div class="field">
                <span class="label">Is Published:</span>
                <span class="value {{ $agent->is_published ? 'success' : 'error' }}">
                    {{ $agent->is_published ? 'YES' : 'NO' }}
                </span>
            </div>
            <div class="field">
                <span class="label">Sync Status:</span>
                <span class="value">{{ $agent->sync_status ?? 'Unknown' }}</span>
            </div>
        </div>

        <div class="section">
            <h2>Version & Dates</h2>
            <div class="field">
                <span class="label">Version:</span>
                <span class="value">{{ $agent->version ?? 'N/A' }}</span>
            </div>
            <div class="field">
                <span class="label">Version Title:</span>
                <span class="value">{{ $agent->version_title ?? 'N/A' }}</span>
            </div>
            <div class="field">
                <span class="label">Created At:</span>
                <span class="value">{{ $agent->created_at }}</span>
            </div>
            <div class="field">
                <span class="label">Updated At:</span>
                <span class="value">{{ $agent->updated_at }}</span>
            </div>
            <div class="field">
                <span class="label">Last Synced At:</span>
                <span class="value">{{ $agent->last_synced_at ?? 'Never' }}</span>
            </div>
        </div>

        <div class="section">
            <h2>Configuration (First 500 chars)</h2>
            <div class="json-display">
                @if($agent->configuration)
                    @php
                        $config = is_string($agent->configuration) ? json_decode($agent->configuration, true) : $agent->configuration;
                        $configStr = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    @endphp
                    <pre>{{ substr($configStr, 0, 500) }}...</pre>
                    <p><small>Total length: {{ strlen($configStr) }} characters</small></p>
                @else
                    <p>No configuration data</p>
                @endif
            </div>
        </div>

        <div class="section">
            <h2>Debug Information</h2>
            <div class="field">
                <span class="label">Model Class:</span>
                <span class="value">{{ get_class($agent) }}</span>
            </div>
            <div class="field">
                <span class="label">Table Name:</span>
                <span class="value">{{ $agent->getTable() }}</span>
            </div>
            <div class="field">
                <span class="label">Connection:</span>
                <span class="value">{{ $agent->getConnectionName() ?? 'default' }}</span>
            </div>
            <div class="field">
                <span class="label">All Attributes Count:</span>
                <span class="value">{{ count($agent->getAttributes()) }}</span>
            </div>
        </div>

        <div class="section">
            <h2>Test Results</h2>
            <div class="field">
                <span class="label">Data Loaded:</span>
                <span class="value success">✓ Success</span>
            </div>
            <div class="field">
                <span class="label">Relationships Work:</span>
                <span class="value {{ $agent->company ? 'success' : 'error' }}">
                    {{ $agent->company ? '✓ Company loaded' : '✗ No company relationship' }}
                </span>
            </div>
            <div class="field">
                <span class="label">Timestamp:</span>
                <span class="value">{{ now() }}</span>
            </div>
        </div>
    </div>
</body>
</html>