<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Detail Export - {{ $call->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header .meta {
            font-size: 14px;
            opacity: 0.9;
        }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .metric-card .label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
        }
        .info-grid .label {
            font-weight: 600;
            color: #4a5568;
        }
        .info-grid .value {
            color: #2d3748;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -20px;
            top: 15px;
            width: 1px;
            height: calc(100% - 10px);
            background: #e2e8f0;
        }
        .timeline-item:last-child:after {
            display: none;
        }
        .timeline-time {
            font-size: 12px;
            color: #718096;
        }
        .timeline-content {
            font-size: 14px;
            color: #2d3748;
            margin-top: 5px;
        }
        .transcript {
            background: white;
            border-radius: 8px;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
        }
        .transcript-entry {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .transcript-entry:last-child {
            border-bottom: none;
        }
        .transcript-role {
            font-weight: 600;
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .transcript-content {
            margin-top: 5px;
            color: #2d3748;
            line-height: 1.5;
        }
        .footer {
            text-align: center;
            color: #718096;
            font-size: 12px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        @media print {
            body {
                padding: 0;
            }
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Call Detail Report</h1>
        <div class="meta">
            <div>Call ID: {{ $call->id }}</div>
            <div>Generated: {{ now()->format('d.m.Y H:i:s') }}</div>
        </div>
    </div>

    <div class="section">
        <h2>Overview</h2>
        <div class="metrics">
            <div class="metric-card">
                <div class="label">Duration</div>
                <div class="value">{{ $duration }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Status</div>
                <div class="value">{{ ucfirst($call->status ?? 'Unknown') }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Cost</div>
                <div class="value">${{ number_format($call->cost ?? 0, 2) }}</div>
            </div>
            @if(isset($sentiment['overall']))
            <div class="metric-card">
                <div class="label">Sentiment</div>
                <div class="value">{{ ucfirst($sentiment['overall']) }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <h2>Call Information</h2>
        <div class="info-grid">
            <div class="label">Start Time:</div>
            <div class="value">{{ \Carbon\Carbon::parse($call->start_timestamp)->format('d.m.Y H:i:s') }}</div>
            
            @if($call->end_timestamp)
            <div class="label">End Time:</div>
            <div class="value">{{ \Carbon\Carbon::parse($call->end_timestamp)->format('d.m.Y H:i:s') }}</div>
            @endif
            
            @if($call->from_phone_number)
            <div class="label">From:</div>
            <div class="value">{{ $call->from_phone_number }}</div>
            @endif
            
            @if($call->to_phone_number)
            <div class="label">To:</div>
            <div class="value">{{ $call->to_phone_number }}</div>
            @endif
            
            @if($call->agent_name)
            <div class="label">Agent:</div>
            <div class="value">{{ $call->agent_name }}</div>
            @endif
            
            @if($call->customer)
            <div class="label">Customer:</div>
            <div class="value">{{ $call->customer->name ?? 'N/A' }}</div>
            @endif
        </div>
    </div>

    @if(!empty($timeline))
    <div class="section">
        <h2>Timeline</h2>
        <div class="timeline">
            @foreach($timeline as $event)
            <div class="timeline-item">
                <div class="timeline-time">{{ $event['time'] ?? '' }}</div>
                <div class="timeline-content">{{ $event['event'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($call->transcript)
    <div class="section">
        <h2>Transcript</h2>
        <div class="transcript">
            @php
                $transcript = is_string($call->transcript) ? json_decode($call->transcript, true) : $call->transcript;
            @endphp
            @if(is_array($transcript))
                @foreach($transcript as $entry)
                <div class="transcript-entry">
                    <div class="transcript-role">{{ $entry['role'] ?? 'Unknown' }}</div>
                    <div class="transcript-content">{{ $entry['content'] ?? '' }}</div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This report was automatically generated by AskPro AI Call Management System</p>
        <p>© {{ date('Y') }} AskPro AI - All rights reserved</p>
    </div>
</body>
</html>