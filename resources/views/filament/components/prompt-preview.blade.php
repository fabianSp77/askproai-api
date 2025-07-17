<div class="space-y-4">
    <div class="bg-gray-100 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">Template Hierarchy</h3>
        @if($template->parent)
            <div class="text-sm text-gray-600">
                @foreach($template->ancestors()->reverse() as $ancestor)
                    {{ $ancestor->name }} → 
                @endforeach
                <span class="font-semibold">{{ $template->name }}</span>
            </div>
        @else
            <div class="text-sm text-gray-600">Root Template</div>
        @endif
    </div>

    <div class="bg-blue-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">Variables</h3>
        <div class="grid grid-cols-2 gap-2">
            @foreach($template->getAllVariables() as $var)
                <div class="text-sm">
                    <span class="font-mono bg-gray-200 px-2 py-1 rounded">{{ "{{$var}}" }}</span>
                    @if(isset($testVars[$var]))
                        → <span class="text-blue-600">{{ $testVars[$var] }}</span>
                    @else
                        → <span class="text-red-600">Not provided</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white border-2 border-gray-300 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">Raw Template</h3>
        <pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-3 rounded">{{ $template->content }}</pre>
    </div>

    <div class="bg-green-50 border-2 border-green-300 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">Compiled Output</h3>
        <div class="whitespace-pre-wrap text-sm">{{ $compiled }}</div>
    </div>

    <div class="bg-yellow-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">Test Variables Used</h3>
        <div class="text-sm font-mono">
            @foreach($testVars as $key => $value)
                <div>{{ $key }}: {{ $value }}</div>
            @endforeach
        </div>
    </div>
</div>