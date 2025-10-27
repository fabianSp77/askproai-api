<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation Flow - Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.5.0/github-markdown.min.css">
    <style>
        .markdown-body {
            box-sizing: border-box;
            min-width: 200px;
            max-width: 980px;
            margin: 0 auto;
            padding: 45px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Conversation Flow Migration - Reports</h1>
            <p class="mt-2 text-gray-600">Generated: {{ now()->format('d.m.Y H:i') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            @if($reports['research_validation'])
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white">Research Validation Report</h2>
                </div>
                <div class="p-6 markdown-body">
                    {!! \Illuminate\Support\Str::markdown($reports['research_validation']) !!}
                </div>
            </div>
            @endif

            @if($reports['baseline_analysis'])
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white">Baseline Analysis Report</h2>
                </div>
                <div class="p-6 markdown-body">
                    {!! \Illuminate\Support\Str::markdown($reports['baseline_analysis']) !!}
                </div>
            </div>
            @endif

            @if($reports['migration_report'])
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-purple-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white">Migration Agent Report</h2>
                </div>
                <div class="p-6 markdown-body">
                    {!! \Illuminate\Support\Str::markdown($reports['migration_report']) !!}
                </div>
            </div>
            @endif
        </div>

        <div class="mt-8">
            <a href="/admin" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                ← Back to Admin
            </a>
        </div>
    </div>
</body>
</html>
