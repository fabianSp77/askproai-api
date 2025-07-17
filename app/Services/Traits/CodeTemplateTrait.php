<?php

namespace App\Services\Traits;

use Illuminate\Support\Str;

trait CodeTemplateTrait
{
    /**
     * Get Filament resource template
     */
    protected function getFilamentResourceTemplate(string $modelName, string $modelClass): string
    {
        return <<<PHP
<?php

namespace App\Filament\Admin\Resources;

use {$modelClass};
use App\Filament\Admin\Resources\\{$modelName}Resource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class {$modelName}Resource extends Resource
{
    protected static ?string \$model = {$modelName}::class;

    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                // Add more form fields here
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                // Add more table columns here
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\List{$modelName}s::route('/'),
            'create' => Pages\Create{$modelName}::route('/create'),
            'edit' => Pages\Edit{$modelName}::route('/{record}/edit'),
        ];
    }
}
PHP;
    }
    
    /**
     * Get MCP server template
     */
    protected function getMCPServerTemplate(string $name, string $className, array $tools): string
    {
        $toolsArray = empty($tools) ? "[\n            // Define tools here\n        ]" : $this->formatToolsArray($tools);
        
        return <<<PHP
<?php

namespace App\Services\MCP;

class {$className} extends BaseMCPServer
{
    protected string \$name = '{$name}';
    protected string \$version = '1.0.0';
    
    /**
     * Get available tools
     */
    public function getTools(): array
    {
        return {$toolsArray};
    }
    
    /**
     * Execute a tool
     */
    public function executeTool(string \$tool, array \$params = []): array
    {
        return match(\$tool) {
            // Implement tool handlers here
            default => ['error' => 'Unknown tool: ' . \$tool]
        };
    }
    
    /**
     * Health check
     */
    public function healthCheck(): array
    {
        return [
            'healthy' => true,
            'status' => 'operational',
            'message' => '{$name} MCP Server is running'
        ];
    }
}
PHP;
    }
    
    /**
     * Get Filament list page template
     */
    protected function getFilamentListPageTemplate(string $modelName, string $resourceName): string
    {
        $pluralName = Str::plural($modelName);
        
        return <<<PHP
<?php

namespace App\Filament\Admin\Resources\\{$resourceName}\Pages;

use App\Filament\Admin\Resources\\{$resourceName};
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class List{$pluralName} extends ListRecords
{
    protected static string \$resource = {$resourceName}::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
PHP;
    }
    
    /**
     * Get Filament create page template
     */
    protected function getFilamentCreatePageTemplate(string $modelName, string $resourceName): string
    {
        return <<<PHP
<?php

namespace App\Filament\Admin\Resources\\{$resourceName}\Pages;

use App\Filament\Admin\Resources\\{$resourceName};
use Filament\Resources\Pages\CreateRecord;

class Create{$modelName} extends CreateRecord
{
    protected static string \$resource = {$resourceName}::class;
}
PHP;
    }
    
    /**
     * Get Filament edit page template
     */
    protected function getFilamentEditPageTemplate(string $modelName, string $resourceName): string
    {
        return <<<PHP
<?php

namespace App\Filament\Admin\Resources\\{$resourceName}\Pages;

use App\Filament\Admin\Resources\\{$resourceName};
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit{$modelName} extends EditRecord
{
    protected static string \$resource = {$resourceName}::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
PHP;
    }
    
    /**
     * Get service template
     */
    protected function getServiceTemplate(string $name, string $className): string
    {
        return <<<PHP
<?php

namespace App\Services;

use App\Contracts\\{$name}ServiceInterface;
use Illuminate\Support\Facades\Log;

class {$className} implements {$name}ServiceInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Main service method
     */
    public function execute(array \$data): array
    {
        try {
            // Implement your business logic here
            
            return [
                'success' => true,
                'data' => \$data
            ];
            
        } catch (\Exception \$e) {
            Log::error('{$className} error', [
                'error' => \$e->getMessage(),
                'data' => \$data
            ]);
            
            return [
                'success' => false,
                'error' => \$e->getMessage()
            ];
        }
    }
}
PHP;
    }
    
    /**
     * Get service interface template
     */
    protected function getServiceInterfaceTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\Contracts;

interface {$name}ServiceInterface
{
    /**
     * Execute the service
     */
    public function execute(array \$data): array;
}
PHP;
    }
    
    /**
     * Get repository template
     */
    protected function getRepositoryTemplate(string $name, string $className, string $modelName): string
    {
        return <<<PHP
<?php

namespace App\Repositories;

use App\Contracts\\{$name}RepositoryInterface;
use App\Models\\{$modelName};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class {$className} implements {$name}RepositoryInterface
{
    protected {$modelName} \$model;
    
    public function __construct({$modelName} \$model)
    {
        \$this->model = \$model;
    }
    
    /**
     * Get all records
     */
    public function all(): Collection
    {
        return \$this->model->all();
    }
    
    /**
     * Find by ID
     */
    public function find(int \$id): ?Model
    {
        return \$this->model->find(\$id);
    }
    
    /**
     * Create new record
     */
    public function create(array \$data): Model
    {
        return \$this->model->create(\$data);
    }
    
    /**
     * Update record
     */
    public function update(int \$id, array \$data): bool
    {
        return \$this->model->find(\$id)->update(\$data);
    }
    
    /**
     * Delete record
     */
    public function delete(int \$id): bool
    {
        return \$this->model->find(\$id)->delete();
    }
}
PHP;
    }
    
    /**
     * Get repository interface template
     */
    protected function getRepositoryInterfaceTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface {$name}RepositoryInterface
{
    public function all(): Collection;
    public function find(int \$id): ?Model;
    public function create(array \$data): Model;
    public function update(int \$id, array \$data): bool;
    public function delete(int \$id): bool;
}
PHP;
    }
    
    /**
     * Get test template
     */
    protected function getTestTemplate(string $name, string $className, string $type): string
    {
        $namespace = $type === 'unit' ? 'Unit' : 'Feature';
        $baseClass = $type === 'unit' ? 'TestCase' : 'TestCase';
        
        return <<<PHP
<?php

namespace Tests\\{$namespace};

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class {$className} extends {$baseClass}
{
    use RefreshDatabase;
    
    /**
     * Test example
     */
    public function test_example(): void
    {
        // Arrange
        
        // Act
        
        // Assert
        \$this->assertTrue(true);
    }
}
PHP;
    }
    
    /**
     * Get migration template
     */
    protected function getMigrationTemplate(string $table, string $className, string $action): string
    {
        $content = match($action) {
            'create' => $this->getCreateTableMigration($table),
            'alter' => $this->getAlterTableMigration($table),
            'drop' => $this->getDropTableMigration($table),
            default => $this->getCreateTableMigration($table)
        };
        
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
{$content}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
    }
    
    /**
     * Get create table migration content
     */
    protected function getCreateTableMigration(string $table): string
    {
        return <<<PHP
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
        });
PHP;
    }
    
    /**
     * Get alter table migration content
     */
    protected function getAlterTableMigration(string $table): string
    {
        return <<<PHP
        Schema::table('{$table}', function (Blueprint \$table) {
            // Add columns
            // \$table->string('new_column')->after('existing_column');
            
            // Modify columns
            // \$table->string('existing_column')->nullable()->change();
            
            // Drop columns
            // \$table->dropColumn('old_column');
        });
PHP;
    }
    
    /**
     * Get drop table migration content
     */
    protected function getDropTableMigration(string $table): string
    {
        return <<<PHP
        Schema::dropIfExists('{$table}');
PHP;
    }
    
    /**
     * Get API controller template
     */
    protected function getApiControllerTemplate(string $resource, string $controllerName, array $actions): string
    {
        $modelName = Str::studly(Str::singular($resource));
        $methods = [];
        
        foreach ($actions as $action) {
            $methods[] = $this->getApiControllerMethod($action, $modelName, $resource);
        }
        
        $methodsString = implode("\n\n", $methods);
        
        return <<<PHP
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\\{$modelName};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class {$controllerName} extends Controller
{
{$methodsString}
}
PHP;
    }
    
    /**
     * Get API controller method
     */
    protected function getApiControllerMethod(string $action, string $modelName, string $resource): string
    {
        return match($action) {
            'index' => $this->getIndexMethod($modelName, $resource),
            'show' => $this->getShowMethod($modelName),
            'store' => $this->getStoreMethod($modelName),
            'update' => $this->getUpdateMethod($modelName),
            'destroy' => $this->getDestroyMethod($modelName),
            default => ''
        };
    }
    
    /**
     * Get index method
     */
    protected function getIndexMethod(string $modelName, string $resource): string
    {
        return <<<PHP
    /**
     * Display a listing of {$resource}.
     */
    public function index(Request \$request): JsonResponse
    {
        \$query = {$modelName}::query();
        
        // Add filters
        if (\$request->has('search')) {
            \$query->where('name', 'like', '%' . \$request->search . '%');
        }
        
        // Paginate results
        \$items = \$query->paginate(\$request->per_page ?? 15);
        
        return response()->json(\$items);
    }
PHP;
    }
    
    /**
     * Get show method
     */
    protected function getShowMethod(string $modelName): string
    {
        return <<<PHP
    /**
     * Display the specified resource.
     */
    public function show({$modelName} \$model): JsonResponse
    {
        return response()->json([
            'data' => \$model
        ]);
    }
PHP;
    }
    
    /**
     * Get store method
     */
    protected function getStoreMethod(string $modelName): string
    {
        return <<<PHP
    /**
     * Store a newly created resource.
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            'name' => 'required|string|max:255',
            // Add more validation rules
        ]);
        
        \$model = {$modelName}::create(\$validated);
        
        return response()->json([
            'data' => \$model,
            'message' => 'Created successfully'
        ], 201);
    }
PHP;
    }
    
    /**
     * Get update method
     */
    protected function getUpdateMethod(string $modelName): string
    {
        return <<<PHP
    /**
     * Update the specified resource.
     */
    public function update(Request \$request, {$modelName} \$model): JsonResponse
    {
        \$validated = \$request->validate([
            'name' => 'sometimes|string|max:255',
            // Add more validation rules
        ]);
        
        \$model->update(\$validated);
        
        return response()->json([
            'data' => \$model,
            'message' => 'Updated successfully'
        ]);
    }
PHP;
    }
    
    /**
     * Get destroy method
     */
    protected function getDestroyMethod(string $modelName): string
    {
        return <<<PHP
    /**
     * Remove the specified resource.
     */
    public function destroy({$modelName} \$model): JsonResponse
    {
        \$model->delete();
        
        return response()->json([
            'message' => 'Deleted successfully'
        ], 204);
    }
PHP;
    }
    
    /**
     * Get API routes template
     */
    protected function getApiRoutesTemplate(string $resource, string $controllerName, array $actions): string
    {
        $routes = [];
        
        if (in_array('index', $actions)) {
            $routes[] = "Route::get('{$resource}', [{$controllerName}::class, 'index']);";
        }
        if (in_array('show', $actions)) {
            $routes[] = "Route::get('{$resource}/{{$resource}}', [{$controllerName}::class, 'show']);";
        }
        if (in_array('store', $actions)) {
            $routes[] = "Route::post('{$resource}', [{$controllerName}::class, 'store']);";
        }
        if (in_array('update', $actions)) {
            $routes[] = "Route::put('{$resource}/{{$resource}}', [{$controllerName}::class, 'update']);";
        }
        if (in_array('destroy', $actions)) {
            $routes[] = "Route::delete('{$resource}/{{$resource}}', [{$controllerName}::class, 'destroy']);";
        }
        
        $routesString = implode("\n", $routes);
        
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\\{$controllerName};

/*
|--------------------------------------------------------------------------
| {$resource} API Routes
|--------------------------------------------------------------------------
*/

{$routesString}
PHP;
    }
    
    /**
     * Get job template
     */
    protected function getJobTemplate(string $name, string $className): string
    {
        return <<<PHP
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class {$className} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array \$data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Process job logic here
            
            Log::info('{$className} completed', ['data' => \$this->data]);
            
        } catch (\Exception \$e) {
            Log::error('{$className} failed', [
                'error' => \$e->getMessage(),
                'data' => \$this->data
            ]);
            
            throw \$e;
        }
    }
}
PHP;
    }
    
    /**
     * Get event template
     */
    protected function getEventTemplate(string $name, string $eventClass): string
    {
        return <<<PHP
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class {$eventClass}
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public array \$data
    ) {
        //
    }
}
PHP;
    }
    
    /**
     * Get listener template
     */
    protected function getListenerTemplate(string $name, string $eventClass, string $listenerClass): string
    {
        return <<<PHP
<?php

namespace App\Listeners;

use App\Events\\{$eventClass};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class {$listenerClass} implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle({$eventClass} \$event): void
    {
        try {
            // Handle the event
            
            Log::info('{$listenerClass} processed', ['data' => \$event->data]);
            
        } catch (\Exception \$e) {
            Log::error('{$listenerClass} failed', [
                'error' => \$e->getMessage(),
                'data' => \$event->data
            ]);
            
            throw \$e;
        }
    }
}
PHP;
    }
    
    /**
     * Get notification template
     */
    protected function getNotificationTemplate(string $name, string $className): string
    {
        return <<<PHP
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class {$className} extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array \$data
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object \$notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object \$notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('{$name} Notification')
            ->line('You have received a new notification.')
            ->action('View Details', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object \$notifiable): array
    {
        return [
            'data' => \$this->data,
            'timestamp' => now()
        ];
    }
}
PHP;
    }
    
    /**
     * Get controller template
     */
    protected function getControllerTemplate(string $name, string $className): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class {$className} extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string \$id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string \$id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, string \$id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string \$id)
    {
        //
    }
}
PHP;
    }
}