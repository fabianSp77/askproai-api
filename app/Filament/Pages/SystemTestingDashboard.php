<?php

namespace App\Filament\Pages;

use App\Models\SystemTestRun;
use App\Services\Testing\CalcomTestRunner;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;

#[Layout('filament-panels::components.layout.base')]
class SystemTestingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Cal.com Testing';
    protected static ?string $title = 'System Testing Dashboard - Cal.com Integration Tests';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->email === 'admin@askproai.de';
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected static string $view = 'filament.pages.system-testing-dashboard';

    // Private properties - custom objects not supported by Livewire 3
    private ?CalcomTestRunner $testRunner = null;
    private ?SystemTestRun $currentTestRun = null;

    // Public reactive properties - Livewire 3 scalar types only
    public array $testRunHistory = [];
    public string $currentTest = '';
    public array $liveOutput = [];
    public bool $isRunning = false;

    public function mount(): void
    {
        $this->testRunner = new CalcomTestRunner();
        $this->loadTestHistory();
    }

    /**
     * Load test history from database
     */
    public function loadTestHistory(): void
    {
        $this->testRunHistory = SystemTestRun::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Run specific test
     */
    public function runTest(string $testType): void
    {
        if (!array_key_exists($testType, SystemTestRun::testTypes())) {
            $this->addError('Invalid test type');
            return;
        }

        $this->isRunning = true;
        $this->currentTest = $testType;
        $this->liveOutput = [];

        try {
            $this->currentTestRun = $this->testRunner->runTest($testType);
            $this->liveOutput = $this->currentTestRun->output ?? [];

            $this->dispatch('test-completed', [
                'testType' => $testType,
                'success' => $this->currentTestRun->succeeded()
            ]);
        } catch (\Exception $e) {
            $this->addError('Test execution failed: ' . $e->getMessage());
            $this->liveOutput[] = ['error' => $e->getMessage()];
        } finally {
            $this->isRunning = false;
            $this->loadTestHistory();
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        $this->isRunning = true;
        $this->liveOutput = [];

        try {
            $results = $this->testRunner->runAllTests();
            $this->liveOutput = $results;

            $this->dispatch('all-tests-completed', [
                'total' => count($results),
                'success' => count(array_filter($results, fn($r) => $r['succeeded']))
            ]);
        } catch (\Exception $e) {
            $this->addError('Test execution failed: ' . $e->getMessage());
        } finally {
            $this->isRunning = false;
            $this->loadTestHistory();
        }
    }

    /**
     * Get test type label
     */
    public function getTestLabel(string $testType): string
    {
        return SystemTestRun::testTypes()[$testType] ?? $testType;
    }

    /**
     * Export test report as JSON
     */
    public function exportReport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $history = SystemTestRun::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->streamDownload(
            function () use ($history) {
                echo json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
            'calcom-test-report-' . now()->format('Y-m-d-His') . '.json'
        );
    }

    /**
     * Download test documentation
     */
    public function downloadDocumentation()
    {
        $filePath = storage_path('app/calcom-test-plan.html');

        if (!file_exists($filePath)) {
            $this->addError('Documentation file not found');
            return response()->noContent();
        }

        return response()->download($filePath, 'calcom-integration-test-plan.html');
    }

    /**
     * Get color for test status
     */
    public function getStatusColor(?string $status): string
    {
        return match($status) {
            SystemTestRun::STATUS_COMPLETED => 'success',
            SystemTestRun::STATUS_FAILED => 'danger',
            SystemTestRun::STATUS_RUNNING => 'warning',
            default => 'gray'
        };
    }

    /**
     * Get icon for test status
     */
    public function getStatusIcon(?string $status): string
    {
        return match($status) {
            SystemTestRun::STATUS_COMPLETED => 'heroicon-o-check-circle',
            SystemTestRun::STATUS_FAILED => 'heroicon-o-x-circle',
            SystemTestRun::STATUS_RUNNING => 'heroicon-o-arrow-path',
            default => 'heroicon-o-question-mark-circle'
        };
    }
}
