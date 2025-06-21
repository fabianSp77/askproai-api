<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeAnalytic;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeVersion;
use Carbon\Carbon;

class KnowledgeCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:cleanup 
                            {--days=90 : Days to retain analytics data}
                            {--versions=10 : Maximum versions to keep per document}
                            {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old knowledge base data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $retentionDays = (int) $this->option('days');
        $maxVersions = (int) $this->option('versions');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No data will be deleted.');
        }

        $this->info('Starting knowledge base cleanup...');
        $this->newLine();

        // Clean old analytics data
        $this->cleanAnalytics($retentionDays, $dryRun);
        $this->newLine();

        // Clean old document versions
        $this->cleanVersions($maxVersions, $dryRun);
        $this->newLine();

        // Clean orphaned data
        $this->cleanOrphans($dryRun);
        $this->newLine();

        // Optimize tables
        if (!$dryRun) {
            $this->optimizeTables();
        }

        $this->info('Cleanup complete!');

        return 0;
    }

    /**
     * Clean old analytics data
     */
    protected function cleanAnalytics(int $retentionDays, bool $dryRun): void
    {
        $this->line('<options=bold>Cleaning analytics data older than ' . $retentionDays . ' days</>');

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        $query = KnowledgeAnalytic::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count > 0) {
            $this->info("Found {$count} analytics records to delete.");
            
            if (!$dryRun) {
                if ($this->confirm("Delete {$count} analytics records?")) {
                    $query->delete();
                    $this->info("Deleted {$count} analytics records.");
                }
            }
        } else {
            $this->info('No old analytics records found.');
        }
    }

    /**
     * Clean old document versions
     */
    protected function cleanVersions(int $maxVersions, bool $dryRun): void
    {
        $this->line('<options=bold>Cleaning old document versions (keeping max ' . $maxVersions . ' per document)</>');

        $documents = KnowledgeDocument::has('versions', '>', $maxVersions)->get();
        $totalDeleted = 0;

        foreach ($documents as $document) {
            $versions = $document->versions()
                ->orderBy('version_number', 'desc')
                ->get();

            $toDelete = $versions->slice($maxVersions);
            
            if ($toDelete->count() > 0) {
                $this->info("Document '{$document->title}' has {$toDelete->count()} old versions.");
                
                if (!$dryRun) {
                    KnowledgeVersion::whereIn('id', $toDelete->pluck('id'))->delete();
                    $totalDeleted += $toDelete->count();
                }
            }
        }

        if ($totalDeleted > 0 || ($dryRun && $documents->count() > 0)) {
            $this->info("Deleted {$totalDeleted} old versions.");
        } else {
            $this->info('No old versions to clean.');
        }
    }

    /**
     * Clean orphaned data
     */
    protected function cleanOrphans(bool $dryRun): void
    {
        $this->line('<options=bold>Cleaning orphaned data</>');

        // Orphaned search indexes
        $orphanedIndexes = \DB::table('knowledge_search_index')
            ->leftJoin('knowledge_documents', 'knowledge_search_index.document_id', '=', 'knowledge_documents.id')
            ->whereNull('knowledge_documents.id')
            ->count();

        if ($orphanedIndexes > 0) {
            $this->info("Found {$orphanedIndexes} orphaned search index entries.");
            
            if (!$dryRun) {
                \DB::table('knowledge_search_index')
                    ->leftJoin('knowledge_documents', 'knowledge_search_index.document_id', '=', 'knowledge_documents.id')
                    ->whereNull('knowledge_documents.id')
                    ->delete();
                $this->info("Deleted {$orphanedIndexes} orphaned search index entries.");
            }
        }

        // Orphaned code snippets
        $orphanedSnippets = \DB::table('knowledge_code_snippets')
            ->leftJoin('knowledge_documents', 'knowledge_code_snippets.document_id', '=', 'knowledge_documents.id')
            ->whereNull('knowledge_documents.id')
            ->count();

        if ($orphanedSnippets > 0) {
            $this->info("Found {$orphanedSnippets} orphaned code snippets.");
            
            if (!$dryRun) {
                \DB::table('knowledge_code_snippets')
                    ->leftJoin('knowledge_documents', 'knowledge_code_snippets.document_id', '=', 'knowledge_documents.id')
                    ->whereNull('knowledge_documents.id')
                    ->delete();
                $this->info("Deleted {$orphanedSnippets} orphaned code snippets.");
            }
        }

        // Unused tags
        $unusedTags = \App\Models\KnowledgeTag::where('usage_count', 0)
            ->doesntHave('documents')
            ->count();

        if ($unusedTags > 0) {
            $this->info("Found {$unusedTags} unused tags.");
            
            if (!$dryRun) {
                \App\Models\KnowledgeTag::where('usage_count', 0)
                    ->doesntHave('documents')
                    ->delete();
                $this->info("Deleted {$unusedTags} unused tags.");
            }
        }

        if ($orphanedIndexes === 0 && $orphanedSnippets === 0 && $unusedTags === 0) {
            $this->info('No orphaned data found.');
        }
    }

    /**
     * Optimize database tables
     */
    protected function optimizeTables(): void
    {
        $this->line('<options=bold>Optimizing database tables</>');

        $tables = [
            'knowledge_documents',
            'knowledge_categories',
            'knowledge_tags',
            'knowledge_document_tag',
            'knowledge_versions',
            'knowledge_search_index',
            'knowledge_code_snippets',
            'knowledge_relationships',
            'knowledge_analytics',
            'knowledge_comments',
        ];

        foreach ($tables as $table) {
            try {
                \DB::statement("OPTIMIZE TABLE {$table}");
                $this->info("Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->warn("Could not optimize table {$table}: " . $e->getMessage());
            }
        }
    }
}