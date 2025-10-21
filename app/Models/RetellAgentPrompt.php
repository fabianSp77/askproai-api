<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * RetellAgentPrompt Model
 *
 * Stores versioned Retell AI agent prompts and function configurations per branch
 */
class RetellAgentPrompt extends Model
{
    protected $fillable = [
        'branch_id',
        'version',
        'prompt_content',
        'functions_config',
        'is_active',
        'is_template',
        'template_name',
        'deployed_at',
        'deployed_by',
        'retell_agent_id',
        'retell_version',
        'validation_status',
        'validation_errors',
        'deployment_notes',
    ];

    protected $casts = [
        'functions_config' => 'json',
        'validation_errors' => 'json',
        'is_active' => 'boolean',
        'is_template' => 'boolean',
        'deployed_at' => 'datetime',
    ];

    /**
     * Get the branch this prompt belongs to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who deployed this version
     */
    public function deployedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }

    /**
     * Get the next version number for this branch
     */
    public static function getNextVersionForBranch(string $branchId): int
    {
        return self::where('branch_id', $branchId)
            ->max('version') + 1;
    }

    /**
     * Get the currently active prompt for a branch
     */
    public static function getActiveForBranch(string $branchId): ?self
    {
        return self::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all templates (global)
     */
    public static function getTemplates()
    {
        return self::where('is_template', true)
            ->orderBy('template_name')
            ->get();
    }

    /**
     * Mark this version as active (deactivate others for same branch)
     */
    public function markAsActive(): void
    {
        // Deactivate all others for this branch
        self::where('branch_id', $this->branch_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activate this one
        $this->update(['is_active' => true]);
    }

    /**
     * Validate the prompt and functions
     */
    public function validate(): array
    {
        $errors = [];

        // Validate prompt content
        if (empty($this->prompt_content)) {
            $errors[] = 'Prompt content cannot be empty';
        } elseif (strlen($this->prompt_content) > 10000) {
            $errors[] = 'Prompt content exceeds maximum length (10000 characters)';
        }

        // Validate functions config
        if (empty($this->functions_config)) {
            $errors[] = 'Functions config cannot be empty';
        } elseif (!is_array($this->functions_config)) {
            $errors[] = 'Functions config must be a valid JSON array';
        } else {
            foreach ($this->functions_config as $function) {
                if (empty($function['name'])) {
                    $errors[] = 'Each function must have a name';
                }
                if (empty($function['type'])) {
                    $errors[] = 'Each function must have a type';
                }
                // Only custom functions require parameters
                // Built-in functions like end_call, transfer_call don't have parameters
                if ($function['type'] === 'custom' && empty($function['parameters'])) {
                    $errors[] = "Function '{$function['name']}' (type: custom) must have parameters definition";
                }
            }
        }

        // Update validation status
        $this->validation_status = empty($errors) ? 'valid' : 'invalid';
        $this->validation_errors = empty($errors) ? null : $errors;
        $this->save();

        return $errors;
    }

    /**
     * Create a new version from this one
     */
    public function createNewVersion(string $promptContent = null, array $functionsConfig = null): self
    {
        return self::create([
            'branch_id' => $this->branch_id,
            'version' => self::getNextVersionForBranch($this->branch_id),
            'prompt_content' => $promptContent ?? $this->prompt_content,
            'functions_config' => $functionsConfig ?? $this->functions_config,
            'is_active' => false,
            'is_template' => false,
            'validation_status' => 'pending',
        ]);
    }
}
