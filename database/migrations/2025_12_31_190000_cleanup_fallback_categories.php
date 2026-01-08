<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceCase;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Cleanup Fallback Categories
 *
 * This migration:
 * 1. Reassigns all ServiceCases from ID 116 and 117 to ID 1
 * 2. Merges useful generic keywords from 117 into ID 1
 * 3. Sets ID 1 confidence to 0.50 (proper fallback threshold)
 * 4. Deletes ID 116 (English "General" duplicate)
 * 5. Deletes ID 117 ("Allgemeine Anfrage" merged into ID 1)
 * 6. Fixes any remaining confidence hierarchy issues
 *
 * @see /root/.claude/plans/silly-wibbling-dragon.md for audit details
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Reassign cases from ID 116 and 117 to ID 1
            ServiceCase::where('category_id', 116)->update(['category_id' => 1]);
            ServiceCase::where('category_id', 117)->update(['category_id' => 1]);

            // 2. Update ID 1 with merged keywords and proper fallback threshold
            $allgemein = ServiceCaseCategory::find(1);
            if ($allgemein) {
                // Generic keywords only - no hardware-specific terms
                $mergedKeywords = [
                    // Core generic terms
                    'hilfe',
                    'problem',
                    'frage',
                    'support',
                    'anfrage',
                    'information',
                    'auskunft',
                    'allgemein',
                    'sonstiges',
                    'andere',
                    'unkategorisiert',
                    // Uncertainty indicators
                    'weiß nicht',
                    'unsicher',
                    'nicht sicher',
                    // General action words
                    'brauche',
                    'benötige',
                    'möchte',
                ];

                $allgemein->update([
                    'intent_keywords' => $mergedKeywords,
                    'confidence_threshold' => 0.50, // Low threshold for fallback
                    'description' => 'Allgemeine IT-Anfragen, die keiner spezifischen Kategorie zugeordnet werden können. Diese Kategorie dient als Fallback für unklare Anfragen.',
                ]);
            }

            // 3. Delete ID 116 (English "General")
            ServiceCaseCategory::where('id', 116)->delete();

            // 4. Delete ID 117 ("Allgemeine Anfrage" - now merged)
            ServiceCaseCategory::where('id', 117)->delete();

            // 5. Fix any remaining confidence hierarchy issues
            // Ensure parent thresholds are always lower than children
            $this->fixConfidenceHierarchy();
        });
    }

    public function down(): void
    {
        // Recreate deleted categories
        DB::transaction(function () {
            // Restore ID 116
            ServiceCaseCategory::create([
                'id' => 116,
                'name' => 'General',
                'slug' => 'general',
                'parent_id' => null,
                'intent_keywords' => ['general', 'question', 'inquiry', 'help'],
                'confidence_threshold' => 0.50,
                'description' => 'General IT inquiries that do not fit into specific categories.',
            ]);

            // Restore ID 117 (child of 116)
            ServiceCaseCategory::create([
                'id' => 117,
                'name' => 'Allgemeine Anfrage',
                'slug' => 'allgemeine-anfrage',
                'parent_id' => 116,
                'intent_keywords' => [
                    'frage', 'information', 'auskunft', 'anfrage', 'weiß nicht',
                    'unsicher', 'hilfe', 'allgemein', 'sonstiges', 'unkategorisiert',
                    'andere', 'monitor', 'bildschirm', 'drucker', 'maus', 'tastatur',
                    'keyboard', 'laptop', 'pc', 'computer', 'rechner', 'hardware',
                    'gerät', 'einschalten', 'startet nicht', 'defekt', 'kaputt',
                    'funktioniert nicht',
                ],
                'confidence_threshold' => 0.50,
                'description' => 'Allgemeine Anfragen ohne spezifische technische Kategorie.',
            ]);

            // Reset ID 1 to original
            $allgemein = ServiceCaseCategory::find(1);
            if ($allgemein) {
                $allgemein->update([
                    'intent_keywords' => ['hilfe', 'problem', 'frage', 'support', 'anfrage'],
                    'confidence_threshold' => 0.70,
                ]);
            }

            // Move some cases back (best effort - exact distribution unknown)
            // This is inherently lossy, but rollback should be rare
        });
    }

    /**
     * Fix confidence hierarchy: Parents must have lower threshold than children
     */
    private function fixConfidenceHierarchy(): void
    {
        // Get all parent categories that have children
        $parents = ServiceCaseCategory::whereNull('parent_id')
            ->whereHas('children')
            ->get();

        foreach ($parents as $parent) {
            $children = ServiceCaseCategory::where('parent_id', $parent->id)->get();

            foreach ($children as $child) {
                // If child has same or lower threshold than parent, increase it
                if ($child->confidence_threshold <= $parent->confidence_threshold) {
                    $child->update([
                        'confidence_threshold' => $parent->confidence_threshold + 0.10,
                    ]);
                }

                // Recursively fix grandchildren
                $this->fixChildConfidence($child);
            }
        }
    }

    /**
     * Recursively fix child confidence thresholds
     */
    private function fixChildConfidence(ServiceCaseCategory $parent): void
    {
        $children = ServiceCaseCategory::where('parent_id', $parent->id)->get();

        foreach ($children as $child) {
            if ($child->confidence_threshold <= $parent->confidence_threshold) {
                $child->update([
                    'confidence_threshold' => $parent->confidence_threshold + 0.05,
                ]);
            }

            $this->fixChildConfidence($child);
        }
    }
};
