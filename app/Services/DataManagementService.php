<?php

namespace App\Services;

use App\Exports\CustomersExport;
use App\Exports\AppointmentsExport;
use App\Exports\FinancialReportExport;
use App\Imports\CustomersImport;
use App\Imports\AppointmentsImport;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\DataArchive;
use App\Models\ImportLog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DataManagementService
{
    /**
     * Export data to various formats
     */
    public function export(string $model, string $format = 'xlsx', array $filters = []): string
    {
        $filename = $this->generateFilename($model, $format);
        $path = 'exports/' . $filename;

        switch ($model) {
            case 'customers':
                $export = new CustomersExport($filters);
                break;
            case 'appointments':
                $export = new AppointmentsExport($filters);
                break;
            case 'financial_report':
                $export = new FinancialReportExport($filters);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported export model: {$model}");
        }

        // Store export based on format
        switch ($format) {
            case 'xlsx':
                Excel::store($export, $path, 'public');
                break;
            case 'csv':
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'pdf':
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::DOMPDF);
                break;
            case 'json':
                $this->exportToJson($export, $path);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        // Log export activity
        $this->logExport($model, $format, $path, $filters);

        return Storage::url($path);
    }

    /**
     * Import data from file
     */
    public function import(string $model, string $filePath, array $options = []): ImportResult
    {
        $importLog = ImportLog::create([
            'model' => $model,
            'file_path' => $filePath,
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            DB::beginTransaction();

            switch ($model) {
                case 'customers':
                    $import = new CustomersImport($options);
                    break;
                case 'appointments':
                    $import = new AppointmentsImport($options);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported import model: {$model}");
            }

            Excel::import($import, $filePath, 'public');

            DB::commit();

            $importLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'rows_imported' => $import->getRowCount(),
                'errors' => $import->getErrors()
            ]);

            return new ImportResult(
                success: true,
                rowsImported: $import->getRowCount(),
                errors: $import->getErrors(),
                warnings: $import->getWarnings()
            );
        } catch (\Exception $e) {
            DB::rollBack();

            $importLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'errors' => ['message' => $e->getMessage()]
            ]);

            Log::error('Import failed', [
                'model' => $model,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            return new ImportResult(
                success: false,
                rowsImported: 0,
                errors: [$e->getMessage()]
            );
        }
    }

    /**
     * Perform bulk operations on models
     */
    public function bulkOperation(string $model, string $operation, array $ids, array $data = []): BulkOperationResult
    {
        $modelClass = $this->getModelClass($model);
        $affected = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            switch ($operation) {
                case 'update':
                    $affected = $this->bulkUpdate($modelClass, $ids, $data);
                    break;

                case 'delete':
                    $affected = $this->bulkDelete($modelClass, $ids, $data['soft'] ?? true);
                    break;

                case 'restore':
                    $affected = $this->bulkRestore($modelClass, $ids);
                    break;

                case 'archive':
                    $affected = $this->bulkArchive($modelClass, $ids);
                    break;

                case 'duplicate':
                    $affected = $this->bulkDuplicate($modelClass, $ids);
                    break;

                case 'tag':
                    $affected = $this->bulkTag($modelClass, $ids, $data['tags'] ?? []);
                    break;

                case 'assign':
                    $affected = $this->bulkAssign($modelClass, $ids, $data);
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported bulk operation: {$operation}");
            }

            DB::commit();

            // Log bulk operation
            $this->logBulkOperation($model, $operation, $ids, $data, $affected);

            return new BulkOperationResult(
                success: true,
                affected: $affected,
                errors: []
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk operation failed', [
                'model' => $model,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);

            return new BulkOperationResult(
                success: false,
                affected: 0,
                errors: [$e->getMessage()]
            );
        }
    }

    /**
     * Archive old data
     */
    public function archiveOldData(int $daysOld = 365): ArchiveResult
    {
        $archived = [];
        $cutoffDate = now()->subDays($daysOld);

        // Archive old appointments
        $appointmentsArchived = $this->archiveOldAppointments($cutoffDate);
        $archived['appointments'] = $appointmentsArchived;

        // Archive old customer data
        $customersArchived = $this->archiveInactiveCustomers($cutoffDate);
        $archived['customers'] = $customersArchived;

        // Archive old logs
        $logsArchived = $this->archiveOldLogs($cutoffDate);
        $archived['logs'] = $logsArchived;

        // Create archive record
        $archive = DataArchive::create([
            'name' => 'Auto Archive ' . now()->format('Y-m-d'),
            'type' => 'automatic',
            'cutoff_date' => $cutoffDate,
            'records_archived' => $archived,
            'file_path' => $this->createArchiveFile($archived),
            'created_by' => auth()->id()
        ]);

        return new ArchiveResult(
            success: true,
            recordsArchived: array_sum($archived),
            details: $archived,
            archiveId: $archive->id
        );
    }

    /**
     * Restore data from archive
     */
    public function restoreFromArchive(int $archiveId, array $options = []): RestoreResult
    {
        $archive = DataArchive::findOrFail($archiveId);
        $restored = [];

        DB::beginTransaction();

        try {
            $archiveData = $this->readArchiveFile($archive->file_path);

            foreach ($archiveData as $model => $records) {
                if (!empty($options['models']) && !in_array($model, $options['models'])) {
                    continue;
                }

                $restoredCount = $this->restoreRecords($model, $records, $options);
                $restored[$model] = $restoredCount;
            }

            DB::commit();

            $archive->update([
                'restored_at' => now(),
                'restored_by' => auth()->id()
            ]);

            return new RestoreResult(
                success: true,
                recordsRestored: array_sum($restored),
                details: $restored
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Archive restoration failed', [
                'archive_id' => $archiveId,
                'error' => $e->getMessage()
            ]);

            return new RestoreResult(
                success: false,
                recordsRestored: 0,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Implement GDPR-compliant data deletion
     */
    public function gdprDelete(string $model, int $id): bool
    {
        $modelClass = $this->getModelClass($model);
        $record = $modelClass::withTrashed()->findOrFail($id);

        DB::beginTransaction();

        try {
            // Anonymize related data instead of hard delete
            $this->anonymizeRelatedData($record);

            // Soft delete the main record
            $record->delete();

            // Log GDPR deletion
            DB::table('gdpr_deletions')->insert([
                'model' => $model,
                'model_id' => $id,
                'anonymized_data' => json_encode($this->getAnonymizedData($record)),
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
                'reason' => 'GDPR request'
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GDPR deletion failed', [
                'model' => $model,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Backup database
     */
    public function createBackup(array $options = []): BackupResult
    {
        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        try {
            // Create backup directory if not exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            // Execute mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.host'),
                config('database.connections.mysql.database'),
                $path
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Backup command failed');
            }

            // Compress backup
            if ($options['compress'] ?? true) {
                $this->compressBackup($path);
                $filename .= '.gz';
            }

            // Upload to cloud storage if configured
            if ($options['cloud'] ?? false) {
                $this->uploadBackupToCloud($path);
            }

            // Log backup
            DB::table('backups')->insert([
                'filename' => $filename,
                'size' => filesize($path),
                'type' => $options['type'] ?? 'manual',
                'created_by' => auth()->id(),
                'created_at' => now()
            ]);

            return new BackupResult(
                success: true,
                filename: $filename,
                size: filesize($path)
            );
        } catch (\Exception $e) {
            Log::error('Backup failed', ['error' => $e->getMessage()]);

            return new BackupResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Data validation and cleaning
     */
    public function validateAndCleanData(string $model, array $options = []): DataCleaningResult
    {
        $modelClass = $this->getModelClass($model);
        $issues = [];
        $fixed = 0;

        // Check for duplicates
        if ($options['check_duplicates'] ?? true) {
            $duplicates = $this->findDuplicates($modelClass, $options['duplicate_fields'] ?? []);
            if ($duplicates->isNotEmpty()) {
                $issues['duplicates'] = $duplicates;
                if ($options['fix_duplicates'] ?? false) {
                    $fixed += $this->mergeDuplicates($duplicates);
                }
            }
        }

        // Validate data integrity
        if ($options['check_integrity'] ?? true) {
            $integrityIssues = $this->checkDataIntegrity($modelClass);
            if (!empty($integrityIssues)) {
                $issues['integrity'] = $integrityIssues;
                if ($options['fix_integrity'] ?? false) {
                    $fixed += $this->fixIntegrityIssues($integrityIssues);
                }
            }
        }

        // Clean orphaned records
        if ($options['clean_orphans'] ?? true) {
            $orphans = $this->findOrphanedRecords($modelClass);
            if ($orphans->isNotEmpty()) {
                $issues['orphans'] = $orphans;
                if ($options['remove_orphans'] ?? false) {
                    $fixed += $this->removeOrphanedRecords($orphans);
                }
            }
        }

        // Normalize data
        if ($options['normalize'] ?? true) {
            $normalized = $this->normalizeData($modelClass);
            $fixed += $normalized;
        }

        return new DataCleaningResult(
            issues: $issues,
            fixed: $fixed,
            recommendations: $this->generateCleaningRecommendations($issues)
        );
    }

    /**
     * Generate data analytics report
     */
    public function generateAnalyticsReport(string $type, array $filters = []): array
    {
        switch ($type) {
            case 'customer_insights':
                return $this->generateCustomerInsights($filters);

            case 'revenue_analysis':
                return $this->generateRevenueAnalysis($filters);

            case 'staff_performance':
                return $this->generateStaffPerformance($filters);

            case 'service_utilization':
                return $this->generateServiceUtilization($filters);

            case 'growth_metrics':
                return $this->generateGrowthMetrics($filters);

            default:
                throw new \InvalidArgumentException("Unsupported report type: {$type}");
        }
    }

    /**
     * Protected helper methods
     */
    protected function bulkUpdate(string $modelClass, array $ids, array $data): int
    {
        // Remove protected fields
        unset($data['id'], $data['created_at'], $data['updated_at']);

        return $modelClass::whereIn('id', $ids)->update($data);
    }

    protected function bulkDelete(string $modelClass, array $ids, bool $soft = true): int
    {
        if ($soft && method_exists($modelClass, 'bootSoftDeletes')) {
            return $modelClass::whereIn('id', $ids)->delete();
        }

        return $modelClass::whereIn('id', $ids)->forceDelete();
    }

    protected function bulkRestore(string $modelClass, array $ids): int
    {
        if (!method_exists($modelClass, 'bootSoftDeletes')) {
            throw new \Exception('Model does not support soft deletes');
        }

        return $modelClass::withTrashed()->whereIn('id', $ids)->restore();
    }

    protected function bulkArchive(string $modelClass, array $ids): int
    {
        $records = $modelClass::whereIn('id', $ids)->get();
        $archived = 0;

        foreach ($records as $record) {
            // Store in archive table
            DB::table('archived_records')->insert([
                'model' => $modelClass,
                'model_id' => $record->id,
                'data' => $record->toJson(),
                'archived_at' => now()
            ]);

            // Mark as archived
            $record->update(['archived_at' => now()]);
            $archived++;
        }

        return $archived;
    }

    protected function archiveOldAppointments(Carbon $cutoffDate): int
    {
        $appointments = Appointment::where('appointment_date', '<', $cutoffDate)
            ->whereIn('status', ['completed', 'cancelled', 'no_show'])
            ->get();

        $archived = 0;
        foreach ($appointments as $appointment) {
            DB::table('appointments_archive')->insert([
                'original_id' => $appointment->id,
                'data' => $appointment->toJson(),
                'archived_at' => now()
            ]);

            $appointment->delete();
            $archived++;
        }

        return $archived;
    }

    protected function getModelClass(string $model): string
    {
        $models = [
            'customers' => Customer::class,
            'appointments' => Appointment::class,
            'companies' => Company::class,
            'staff' => \App\Models\Staff::class,
            'services' => \App\Models\Service::class,
        ];

        if (!isset($models[$model])) {
            throw new \InvalidArgumentException("Unknown model: {$model}");
        }

        return $models[$model];
    }

    protected function generateFilename(string $model, string $format): string
    {
        return sprintf(
            '%s_export_%s_%s.%s',
            $model,
            auth()->user()->company_id ?? 'system',
            now()->format('Y-m-d_His'),
            $format
        );
    }
}

// Result classes
class ImportResult
{
    public function __construct(
        public bool $success,
        public int $rowsImported,
        public array $errors = [],
        public array $warnings = []
    ) {}
}

class BulkOperationResult
{
    public function __construct(
        public bool $success,
        public int $affected,
        public array $errors = []
    ) {}
}

class ArchiveResult
{
    public function __construct(
        public bool $success,
        public int $recordsArchived,
        public array $details = [],
        public ?int $archiveId = null
    ) {}
}

class RestoreResult
{
    public function __construct(
        public bool $success,
        public int $recordsRestored,
        public array $details = [],
        public ?string $error = null
    ) {}
}

class BackupResult
{
    public function __construct(
        public bool $success,
        public ?string $filename = null,
        public ?int $size = null,
        public ?string $error = null
    ) {}
}

class DataCleaningResult
{
    public function __construct(
        public array $issues,
        public int $fixed,
        public array $recommendations
    ) {}
}