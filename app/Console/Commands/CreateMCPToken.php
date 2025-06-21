<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;

class CreateMCPToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:create-token {email} {--name=mcp-access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API token for MCP access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }
        
        // Create token with all abilities for MCP access
        $token = $user->createToken($tokenName, ['*']);
        
        $this->info("✅ MCP Token created successfully!");
        $this->newLine();
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Token Name: {$tokenName}");
        $this->newLine();
        $this->line("🔑 Your MCP Access Token:");
        $this->newLine();
        $this->warn($token->plainTextToken);
        $this->newLine();
        $this->info("⚠️  Save this token securely! It won't be shown again.");
        $this->newLine();
        $this->info("📋 Usage example:");
        $this->line("curl -H \"Authorization: Bearer {$token->plainTextToken}\" \\");
        $this->line("  https://api.askproai.de/api/mcp/info");
        
        return 0;
    }
}