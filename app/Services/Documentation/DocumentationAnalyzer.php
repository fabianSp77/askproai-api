<?php

namespace App\Services\Documentation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentationAnalyzer
{
    /**
     * Mapping von Code-Bereichen zu betroffenen Dokumentations-Dateien
     */
    private array $documentMapping = [
        // API-Dokumentation
        'app/Http/Controllers/Api' => [
            'docs/api/endpoints.md',
            'public/docs/api/swagger/openapi.json',
            'docs/api/webhook-schemas.md'
        ],
        
        // MCP-Server Dokumentation
        'app/Services/MCP' => [
            'docs/MCP_COMPLETE_OVERVIEW.md',
            'docs/MCP_INTEGRATION_GUIDE.md',
            'CLAUDE.md' // MCP-Server Übersicht Section
        ],
        
        // Konfiguration
        'config/' => [
            'docs/DEPLOYMENT_GUIDE.md',
            '.env.example',
            'docs/TROUBLESHOOTING_GUIDE.md'
        ],
        
        // Datenbank
        'database/migrations' => [
            'docs/architecture/database-schema.md',
            'docs/DATABASE_SCHEMA.md',
            'CLAUDE.md' // Database Section
        ],
        
        // Filament Admin
        'app/Filament/Admin' => [
            'docs/ADMIN_INTERFACE_GUIDE.md',
            'CLAUDE.md', // UI/UX Section
            'docs/FILAMENT_RESOURCES.md'
        ],
        
        // Services
        'app/Services' => [
            'docs/SERVICE_ARCHITECTURE.md',
            'docs/TESTING_STRATEGY.md'
        ],
        
        // Models
        'app/Models' => [
            'docs/architecture/database-schema.md',
            'docs/DATABASE_SCHEMA.md'
        ],
        
        // Routes
        'routes/' => [
            'docs/api/routes-generated.md',
            'docs/api/endpoints.md'
        ]
    ];
    
    /**
     * Keywords die auf Breaking Changes hindeuten
     */
    private array $breakingChangeIndicators = [
        'breaking:',
        'BREAKING CHANGE:',
        'BREAKING:',
        '!:',
        'removed',
        'deleted',
        'deprecated',
        'incompatible',
        'migration required'
    ];
    
    /**
     * Analysiert Änderungen und ermittelt betroffene Dokumentation
     */
    public function analyzeChanges(string $commitMsg, array $changedFiles): ChangeSet
    {
        $changeSet = new ChangeSet();
        
        // 1. Analysiere Commit-Message
        $this->analyzeCommitMessage($commitMsg, $changeSet);
        
        // 2. Analysiere geänderte Dateien
        foreach ($changedFiles as $file) {
            if (empty($file)) continue;
            
            $affectedDocs = $this->findAffectedDocuments($file);
            $changeSet->addAffectedDocuments($file, $affectedDocs);
            
            // Analysiere Datei-Inhalt wenn möglich
            if (File::exists($file)) {
                $this->analyzeFileContent($file, $changeSet);
            }
        }
        
        // 3. Erkenne Breaking Changes
        if ($this->isBreakingChange($commitMsg, $changedFiles)) {
            $changeSet->markAsBreaking();
            $changeSet->addAffectedDocument('CHANGELOG.md');
            $changeSet->addAffectedDocument('docs/BREAKING_CHANGES.md');
        }
        
        // 4. Erkenne neue Features
        if ($this->isNewFeature($commitMsg, $changedFiles)) {
            $changeSet->markAsFeature();
            $changeSet->addAffectedDocument('CHANGELOG.md');
            $changeSet->addAffectedDocument('docs/FEATURES.md');
        }
        
        return $changeSet;
    }
    
    /**
     * Findet alle Dokumentations-Dateien die von einer Code-Änderung betroffen sind
     */
    private function findAffectedDocuments(string $file): array
    {
        $affected = [];
        
        // Direkte Mappings prüfen
        foreach ($this->documentMapping as $pattern => $docs) {
            if (Str::startsWith($file, $pattern)) {
                $affected = array_merge($affected, $docs);
            }
        }
        
        // Spezielle Fälle
        if (Str::endsWith($file, 'Resource.php') && Str::contains($file, 'Filament')) {
            $affected[] = 'docs/FILAMENT_RESOURCES.md';
            $affected[] = 'docs/ADMIN_INTERFACE_GUIDE.md';
        }
        
        if (Str::endsWith($file, 'Controller.php')) {
            $affected[] = 'docs/api/endpoints.md';
        }
        
        if (Str::contains($file, 'webhook') || Str::contains($file, 'Webhook')) {
            $affected[] = 'docs/api/webhook-schemas.md';
            $affected[] = 'RETELL_WEBHOOK_SETUP.md';
        }
        
        if (Str::contains($file, 'mcp') || Str::contains($file, 'MCP')) {
            $affected[] = 'docs/MCP_COMPLETE_OVERVIEW.md';
        }
        
        if (Str::endsWith($file, '.env.example')) {
            $affected[] = 'docs/DEPLOYMENT_GUIDE.md';
            $affected[] = 'CLAUDE.md'; // Environment Configuration Section
        }
        
        return array_unique($affected);
    }
    
    /**
     * Analysiert Commit-Message für relevante Informationen
     */
    private function analyzeCommitMessage(string $message, ChangeSet $changeSet): void
    {
        // Conventional Commits Pattern
        if (preg_match('/^(feat|fix|docs|style|refactor|test|chore|perf|ci|build|breaking)(\(.+\))?!?:\s*(.+)$/i', $message, $matches)) {
            $type = strtolower($matches[1]);
            $scope = trim($matches[2] ?? '', '()');
            $description = $matches[3];
            
            $changeSet->setCommitType($type);
            if ($scope) {
                $changeSet->setScope($scope);
            }
            $changeSet->setDescription($description);
            
            // Spezielle Behandlung für bestimmte Typen
            switch ($type) {
                case 'feat':
                    $changeSet->addAffectedDocument('CHANGELOG.md');
                    $changeSet->addAffectedDocument('docs/FEATURES.md');
                    break;
                case 'fix':
                    $changeSet->addAffectedDocument('CHANGELOG.md');
                    break;
                case 'docs':
                    // Dokumentations-Änderungen brauchen meist keine weiteren Updates
                    break;
                case 'breaking':
                    $changeSet->markAsBreaking();
                    $changeSet->addAffectedDocument('docs/BREAKING_CHANGES.md');
                    break;
            }
        }
    }
    
    /**
     * Analysiert Datei-Inhalt für zusätzliche Hinweise
     */
    private function analyzeFileContent(string $file, ChangeSet $changeSet): void
    {
        $content = File::get($file);
        
        // Suche nach TODO/FIXME Kommentaren die Dokumentation erwähnen
        if (preg_match_all('/(?:TODO|FIXME).*?(?:doc|documentation|update docs)/i', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $changeSet->addTodo($match);
            }
        }
        
        // Suche nach @deprecated Tags
        if (preg_match_all('/@deprecated\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[1] as $deprecation) {
                $changeSet->addDeprecation($deprecation);
                $changeSet->addAffectedDocument('docs/DEPRECATIONS.md');
            }
        }
        
        // Suche nach neuen öffentlichen Methoden in Controllers
        if (Str::endsWith($file, 'Controller.php')) {
            if (preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $method) {
                    if (!in_array($method, ['__construct', '__destruct', '__call'])) {
                        $changeSet->addNewMethod($file, $method);
                    }
                }
            }
        }
    }
    
    /**
     * Prüft ob es sich um einen Breaking Change handelt
     */
    private function isBreakingChange(string $commitMsg, array $files): bool
    {
        // Check commit message
        $lowerMsg = strtolower($commitMsg);
        foreach ($this->breakingChangeIndicators as $indicator) {
            if (str_contains($lowerMsg, strtolower($indicator))) {
                return true;
            }
        }
        
        // Check for removal of public methods/classes
        foreach ($files as $file) {
            if (str_starts_with($file, 'D ')) { // Git deletion marker
                if (str_contains($file, 'app/Http/Controllers') || 
                    str_contains($file, 'app/Services') ||
                    str_contains($file, 'app/Models')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Prüft ob es sich um ein neues Feature handelt
     */
    private function isNewFeature(string $commitMsg, array $files): bool
    {
        // Check commit message
        if (preg_match('/^feat(\(.+\))?:/i', $commitMsg)) {
            return true;
        }
        
        // Check for new files in key directories
        foreach ($files as $file) {
            if (str_starts_with($file, 'A ')) { // Git addition marker
                if (str_contains($file, 'app/Http/Controllers') || 
                    str_contains($file, 'app/Services') ||
                    str_contains($file, 'app/Filament')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Ermittelt alle Dokumentations-Dateien im Projekt
     */
    public function getAllDocumentationFiles(): Collection
    {
        $docs = collect();
        
        // Markdown files im Root
        $docs = $docs->merge(
            File::glob(base_path('*.md'))
        );
        
        // Docs Verzeichnis
        if (File::isDirectory(base_path('docs'))) {
            $docs = $docs->merge(
                File::allFiles(base_path('docs'))
                    ->filter(fn($file) => $file->getExtension() === 'md')
                    ->map(fn($file) => $file->getPathname())
            );
        }
        
        // MkDocs Verzeichnis
        if (File::isDirectory(base_path('docs_mkdocs'))) {
            $docs = $docs->merge(
                File::allFiles(base_path('docs_mkdocs'))
                    ->filter(fn($file) => $file->getExtension() === 'md')
                    ->map(fn($file) => $file->getPathname())
            );
        }
        
        // API Docs
        if (File::exists(public_path('docs/api/swagger/openapi.json'))) {
            $docs->push(public_path('docs/api/swagger/openapi.json'));
        }
        
        return $docs->unique()->sort();
    }
    
    /**
     * Berechnet Dokumentations-Gesundheits-Score
     */
    public function calculateHealthScore(): float
    {
        $totalDocs = $this->getAllDocumentationFiles()->count();
        if ($totalDocs === 0) return 0;
        
        $outdatedCount = 0;
        $brokenLinkCount = 0;
        
        foreach ($this->getAllDocumentationFiles() as $doc) {
            // Check freshness
            $lastModified = File::lastModified($doc);
            $daysSinceUpdate = (time() - $lastModified) / 86400;
            
            if ($daysSinceUpdate > 30) {
                $outdatedCount++;
            }
            
            // Check for broken links (simplified)
            $content = File::get($doc);
            if (preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches)) {
                foreach ($matches[2] as $link) {
                    if (str_starts_with($link, '/') || str_starts_with($link, './')) {
                        $linkedFile = base_path(ltrim($link, './'));
                        if (!File::exists($linkedFile)) {
                            $brokenLinkCount++;
                        }
                    }
                }
            }
        }
        
        // Calculate score (0-100)
        $freshnessScore = max(0, 100 - ($outdatedCount / $totalDocs * 100));
        $linkScore = max(0, 100 - ($brokenLinkCount * 5)); // -5 points per broken link
        
        return round(($freshnessScore + $linkScore) / 2, 2);
    }
}

/**
 * Repräsentiert eine Sammlung von Änderungen die Dokumentation betreffen
 */
class ChangeSet
{
    private array $affectedDocuments = [];
    private array $fileChanges = [];
    private bool $isBreaking = false;
    private bool $isFeature = false;
    private ?string $commitType = null;
    private ?string $scope = null;
    private ?string $description = null;
    private array $todos = [];
    private array $deprecations = [];
    private array $newMethods = [];
    
    public function addAffectedDocuments(string $sourceFile, array $documents): void
    {
        $this->fileChanges[$sourceFile] = $documents;
        foreach ($documents as $doc) {
            $this->addAffectedDocument($doc);
        }
    }
    
    public function addAffectedDocument(string $document): void
    {
        if (!in_array($document, $this->affectedDocuments)) {
            $this->affectedDocuments[] = $document;
        }
    }
    
    public function markAsBreaking(): void
    {
        $this->isBreaking = true;
    }
    
    public function markAsFeature(): void
    {
        $this->isFeature = true;
    }
    
    public function setCommitType(string $type): void
    {
        $this->commitType = $type;
    }
    
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }
    
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
    
    public function addTodo(string $todo): void
    {
        $this->todos[] = $todo;
    }
    
    public function addDeprecation(string $deprecation): void
    {
        $this->deprecations[] = $deprecation;
    }
    
    public function addNewMethod(string $file, string $method): void
    {
        $this->newMethods[] = ['file' => $file, 'method' => $method];
    }
    
    public function isEmpty(): bool
    {
        return empty($this->affectedDocuments) && 
               empty($this->todos) && 
               empty($this->deprecations) &&
               empty($this->newMethods);
    }
    
    public function getAffectedDocuments(): array
    {
        return $this->affectedDocuments;
    }
    
    public function isBreaking(): bool
    {
        return $this->isBreaking;
    }
    
    public function isFeature(): bool
    {
        return $this->isFeature;
    }
    
    public function getSummary(): array
    {
        return [
            'type' => $this->commitType,
            'scope' => $this->scope,
            'description' => $this->description,
            'breaking' => $this->isBreaking,
            'feature' => $this->isFeature,
            'affected_documents' => $this->affectedDocuments,
            'file_changes' => $this->fileChanges,
            'todos' => $this->todos,
            'deprecations' => $this->deprecations,
            'new_methods' => $this->newMethods
        ];
    }
}