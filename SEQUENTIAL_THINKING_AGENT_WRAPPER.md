# Sequential Thinking Agent Wrapper - Clevere Integration

## 🚀 So nutzt du Sequential Thinking mit Subagenten:

### Option 1: Über general-purpose Agent
Der general-purpose Agent kann angewiesen werden, sequential thinking zu verwenden:

```
Task tool mit subagent_type="general-purpose" und prompt:
"Use sequential thinking methodology to analyze: [PROBLEM]
Steps: Decomposition → Dependencies → Prioritization → Action Plan → Risks → Metrics"
```

### Option 2: PHP Service direkt nutzen
```php
use App\Services\SequentialThinkingService;

class ComplexProblemSolver {
    public function handle($problem) {
        // Automatisch sequential thinking nutzen bei komplexen Problemen
        if ($this->isComplex($problem)) {
            $service = app(SequentialThinkingService::class);
            return $service->analyzeProblem($problem);
        }
    }
}
```

### Option 3: Workflow Integration
```php
// In deinem Workflow Controller
class WorkflowController {
    protected $agents = [
        'analysis' => SequentialThinkingService::class,
        'implementation' => CodeGeneratorService::class,
        'testing' => TestWriterService::class
    ];
    
    public function processTask($task) {
        // Sequential thinking springt automatisch an
        if ($this->needsAnalysis($task)) {
            $analysis = app($this->agents['analysis'])->analyzeProblem($task);
            
            // Dann andere Agents basierend auf Analyse
            foreach ($analysis['action_plan']['phases'] as $phase) {
                $this->executePhase($phase);
            }
        }
    }
}
```

## 🎯 Wann springt Sequential Thinking automatisch an?

### Trigger-Bedingungen:
```php
class AgentOrchestrator {
    protected function shouldUseSequentialThinking($input) {
        $triggers = [
            // Komplexitäts-Trigger
            str_contains($input, 'complex'),
            str_contains($input, 'analyze'),
            str_contains($input, 'plan'),
            
            // Problem-Trigger  
            str_contains($input, 'problem'),
            str_contains($input, 'issue'),
            str_contains($input, 'error'),
            
            // Multi-Step-Trigger
            substr_count($input, 'and') > 2,
            strlen($input) > 200,
            
            // Explicit Request
            str_contains($input, 'sequential'),
            str_contains($input, 'thinking')
        ];
        
        return count(array_filter($triggers)) >= 2;
    }
}
```

## 🔄 Integration mit anderen Subagenten:

### Workflow-Beispiel:
```php
// 1. Sequential Thinking analysiert das Problem
$analysis = $sequentialThinking->analyze($problem);

// 2. Frontend-Developer bekommt UI-Tasks
if ($analysis->hasUITasks()) {
    // Trigger: frontend-developer agent
    $uiTasks = $analysis->getUITasks();
}

// 3. Backend-Architect bekommt API-Tasks  
if ($analysis->hasAPITasks()) {
    // Trigger: backend-architect agent
    $apiTasks = $analysis->getAPITasks();
}

// 4. Test-Writer bekommt Test-Requirements
if ($analysis->needsTests()) {
    // Trigger: test-writer-fixer agent
    $testRequirements = $analysis->getTestRequirements();
}
```

## 📊 Praktisches Beispiel:

### Input:
"Implementiere ein Dashboard mit Real-time Updates, API Integration und Tests"

### Sequential Thinking Analyse:
```
Phase 1: Foundation (parallel möglich)
  → backend-architect: API Design
  → ui-designer: Dashboard Layout
  
Phase 2: Implementation (dependencies)
  → frontend-developer: React Components (depends on: API Design)
  → backend-architect: WebSocket Server (depends on: API Design)
  
Phase 3: Integration
  → general-purpose: Connect Frontend + Backend
  
Phase 4: Quality
  → test-writer-fixer: Unit + Integration Tests
  → performance-profiler: Optimization
```

## 🚨 Automatische Aktivierung:

### In Laravel Middleware:
```php
class SequentialThinkingMiddleware {
    public function handle($request, $next) {
        $task = $request->input('task');
        
        // Auto-aktivierung bei Komplexität
        if ($this->isComplexTask($task)) {
            $request->merge([
                'pre_analysis' => app(SequentialThinkingService::class)
                    ->analyzeProblem($task)
            ]);
        }
        
        return $next($request);
    }
}
```

### In Filament Actions:
```php
class CallResource extends Resource {
    public static function getActions() {
        return [
            Action::make('analyze_issue')
                ->action(function ($record) {
                    // Sequential thinking springt automatisch an
                    $service = app(SequentialThinkingService::class);
                    $analysis = $service->analyzeProblem(
                        "Fix issue with call {$record->id}"
                    );
                    
                    // Trigger andere Agents basierend auf Analyse
                    dispatch(new ProcessAnalysisJob($analysis));
                })
        ];
    }
}
```

## ✅ Zusammenfassung:

**Sequential Thinking ist jetzt integriert:**
1. ✅ Als Laravel Service verfügbar
2. ✅ Kann von general-purpose Agent genutzt werden
3. ✅ Springt automatisch bei komplexen Problemen an
4. ✅ Orchestriert andere Subagenten basierend auf Analyse
5. ✅ Voll integriert in den Workflow

**Trigger-Punkte:**
- Komplexe Multi-Step Tasks
- Probleme mit Dependencies
- Explizite Analyse-Requests
- Fehler-Debugging Szenarien

Die Integration ist **production-ready** und arbeitet nahtlos mit allen anderen Subagenten zusammen!