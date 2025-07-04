<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Context7 Service
 * 
 * This service provides integration with Context7 documentation API
 * to retrieve library documentation and code examples for the project.
 */
class Context7Service
{
    const CACHE_PREFIX = 'context7:';
    const CACHE_TTL = 3600; // 1 hour
    
    protected array $projectLibraries = [
        'laravel' => '/context7/laravel',
        'filament' => '/filamentphp/filament',
        'retell' => '/context7/docs_retellai_com',
        'calcom' => '/calcom/cal.com',
        'horizon' => '/laravel/horizon',
        'livewire' => '/livewire/livewire'
    ];

    /**
     * Search for a library by name
     */
    public function searchLibrary(string $libraryName): array
    {
        $cacheKey = self::CACHE_PREFIX . 'search:' . md5($libraryName);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($libraryName) {
            // Since we don't have a real Context7 API, we'll simulate with our known libraries
            $results = [];
            $searchTerm = strtolower($libraryName);
            
            foreach ($this->projectLibraries as $name => $id) {
                if (str_contains($name, $searchTerm)) {
                    $results[] = [
                        'name' => ucfirst($name),
                        'library_id' => $id,
                        'description' => $this->getLibraryDescription($name),
                        'trust_score' => $this->getTrustScore($name)
                    ];
                }
            }
            
            // Add exact matches from known libraries
            if (isset($this->projectLibraries[$searchTerm])) {
                array_unshift($results, [
                    'name' => ucfirst($searchTerm),
                    'library_id' => $this->projectLibraries[$searchTerm],
                    'description' => $this->getLibraryDescription($searchTerm),
                    'trust_score' => $this->getTrustScore($searchTerm)
                ]);
            }
            
            return array_unique($results, SORT_REGULAR);
        });
    }

    /**
     * Get documentation for a specific library
     */
    public function getLibraryDocs(string $libraryId, ?string $topic = null, int $maxTokens = 5000): array
    {
        $cacheKey = self::CACHE_PREFIX . 'docs:' . md5($libraryId . ':' . $topic . ':' . $maxTokens);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($libraryId, $topic, $maxTokens) {
            // Simulate documentation retrieval
            $libraryName = $this->getLibraryNameFromId($libraryId);
            
            return [
                'library_id' => $libraryId,
                'library_name' => $libraryName,
                'topic' => $topic,
                'content' => $this->generateDocumentationContent($libraryName, $topic),
                'snippets_count' => $this->getSnippetsCount($libraryName)
            ];
        });
    }

    /**
     * Search for code examples in a library
     */
    public function searchCodeExamples(string $libraryId, string $query): array
    {
        $cacheKey = self::CACHE_PREFIX . 'examples:' . md5($libraryId . ':' . $query);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($libraryId, $query) {
            $libraryName = $this->getLibraryNameFromId($libraryId);
            
            // Generate relevant code examples based on library and query
            return $this->generateCodeExamples($libraryName, $query);
        });
    }

    /**
     * Get library description
     */
    protected function getLibraryDescription(string $libraryName): string
    {
        $descriptions = [
            'laravel' => 'PHP web application framework with expressive, elegant syntax',
            'filament' => 'Beautiful full-stack components for Laravel admin panels',
            'retell' => 'AI phone agents platform for voice conversations',
            'calcom' => 'Open source scheduling infrastructure',
            'horizon' => 'Queue monitoring for Laravel',
            'livewire' => 'Full-stack framework for Laravel'
        ];
        
        return $descriptions[$libraryName] ?? 'Library for ' . ucfirst($libraryName);
    }

    /**
     * Get trust score for a library
     */
    protected function getTrustScore(string $libraryName): float
    {
        $scores = [
            'laravel' => 10,
            'filament' => 8.3,
            'retell' => 8,
            'calcom' => 9.2,
            'horizon' => 9.5,
            'livewire' => 9
        ];
        
        return $scores[$libraryName] ?? 7.0;
    }

    /**
     * Get snippets count for a library
     */
    protected function getSnippetsCount(string $libraryName): int
    {
        $counts = [
            'laravel' => 5724,
            'filament' => 2337,
            'retell' => 405,
            'calcom' => 388,
            'horizon' => 150,
            'livewire' => 890
        ];
        
        return $counts[$libraryName] ?? 100;
    }

    /**
     * Get library name from ID
     */
    protected function getLibraryNameFromId(string $libraryId): string
    {
        $flipped = array_flip($this->projectLibraries);
        return $flipped[$libraryId] ?? 'unknown';
    }

    /**
     * Generate documentation content based on library and topic
     */
    protected function generateDocumentationContent(string $libraryName, ?string $topic): string
    {
        $baseContent = "# {$libraryName} Documentation\n\n";
        
        // Add library-specific documentation
        switch ($libraryName) {
            case 'laravel':
                $baseContent .= $this->getLaravelDocumentation($topic);
                break;
            case 'filament':
                $baseContent .= $this->getFilamentDocumentation($topic);
                break;
            case 'retell':
                $baseContent .= $this->getRetellDocumentation($topic);
                break;
            case 'calcom':
                $baseContent .= $this->getCalcomDocumentation($topic);
                break;
            default:
                $baseContent .= "Documentation for {$libraryName}";
        }
        
        return $baseContent;
    }

    /**
     * Get Laravel documentation
     */
    protected function getLaravelDocumentation(?string $topic): string
    {
        if ($topic === 'multi-tenancy') {
            return <<<DOC
## Multi-Tenancy in Laravel

Laravel provides several approaches for implementing multi-tenancy:

### 1. Single Database with Tenant Scope
```php
// Global scope for tenant isolation
class TenantScope implements Scope
{
    public function apply(Builder \$builder, Model \$model)
    {
        \$builder->where('company_id', auth()->user()->company_id);
    }
}
```

### 2. Repository Pattern
```php
abstract class BaseRepository
{
    protected function applyTenantScope(\$query)
    {
        return \$query->where('company_id', \$this->getTenantId());
    }
}
```

### 3. Middleware for Tenant Resolution
```php
class TenantMiddleware
{
    public function handle(\$request, Closure \$next)
    {
        \$tenant = \$this->resolveTenant(\$request);
        app()->instance('tenant', \$tenant);
        return \$next(\$request);
    }
}
```
DOC;
        }
        
        return "General Laravel documentation. Specify a topic for more detailed information.";
    }

    /**
     * Get Filament documentation
     */
    protected function getFilamentDocumentation(?string $topic): string
    {
        if ($topic === 'livewire-v3') {
            return <<<DOC
## Filament with Livewire v3

### Common Issues and Solutions:

1. **Method not found errors**
```php
// Ensure you're using the correct Livewire component base class
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class MyComponent extends Component implements HasForms
{
    use InteractsWithForms;
}
```

2. **Alpine.js conflicts**
```javascript
// Use Filament's Alpine extensions
Alpine.data('myComponent', () => ({
    init() {
        this.\$watch('property', value => {
            // Handle changes
        });
    }
}));
```

3. **Form state management**
```php
public function mount(): void
{
    \$this->form->fill([
        'name' => \$this->record->name,
    ]);
}
```
DOC;
        }
        
        return "General Filament documentation. Specify a topic for more detailed information.";
    }

    /**
     * Get Retell documentation
     */
    protected function getRetellDocumentation(?string $topic): string
    {
        if ($topic === 'webhook-signature') {
            return <<<DOC
## Retell Webhook Signature Verification

### Implementation:
```php
class VerifyRetellSignature
{
    public function handle(\$request, Closure \$next)
    {
        \$signature = \$request->header('x-retell-signature');
        \$body = \$request->getContent();
        \$secret = config('services.retell.api_key'); // Note: Uses API key, not separate secret
        
        \$expectedSignature = hash_hmac('sha256', \$body, \$secret);
        
        if (!\$signature || !\hash_equals(\$expectedSignature, \$signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        return \$next(\$request);
    }
}
```

### Custom Functions:
```json
{
    "name": "book_appointment",
    "description": "Books an appointment for the customer",
    "parameters": {
        "type": "object",
        "properties": {
            "date": {"type": "string"},
            "time": {"type": "string"},
            "service": {"type": "string"}
        }
    }
}
```
DOC;
        }
        
        return "General Retell.ai documentation. Specify a topic for more detailed information.";
    }

    /**
     * Get Cal.com documentation  
     */
    protected function getCalcomDocumentation(?string $topic): string
    {
        if ($topic === 'api-v2') {
            return <<<DOC
## Cal.com API v2

### Authentication:
```php
\$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . \$apiKey,
    'cal-api-version' => '2',
])->get('https://api.cal.com/v2/event-types');
```

### Creating Events:
```php
\$response = Http::post('https://api.cal.com/v2/bookings', [
    'eventTypeId' => 123,
    'start' => '2024-01-20T10:00:00Z',
    'attendee' => [
        'email' => 'customer@example.com',
        'name' => 'John Doe',
        'timeZone' => 'Europe/Berlin'
    ]
]);
```

### Webhook Events:
- booking.created
- booking.cancelled
- booking.rescheduled
DOC;
        }
        
        return "General Cal.com documentation. Specify a topic for more detailed information.";
    }

    /**
     * Generate code examples based on library and query
     */
    protected function generateCodeExamples(string $libraryName, string $query): array
    {
        // This would normally query a real API
        // For now, return contextual examples based on library and query
        
        $examples = [];
        
        if (str_contains($query, 'webhook') && $libraryName === 'retell') {
            $examples[] = [
                'title' => 'Webhook signature verification',
                'code' => file_get_contents(__DIR__ . '/../../app/Http/Middleware/VerifyRetellSignature.php'),
                'language' => 'php'
            ];
        }
        
        if (str_contains($query, 'multi-tenant') && $libraryName === 'laravel') {
            $examples[] = [
                'title' => 'Tenant scope implementation',
                'code' => file_get_contents(__DIR__ . '/../../app/Scopes/TenantScope.php'),
                'language' => 'php'
            ];
        }
        
        return $examples;
    }
}