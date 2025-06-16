<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Tenant anlegen / holen
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'askproai'],
            [
                'name'    => 'AskProAI',
                'api_key' => Str::random(32),
            ],
        );

        // 2) Admin-User anlegen / holen
        $admin = User::firstOrCreate(
            ['email' => 'fabian@askproai.de'],
            [
                'name'              => 'Fabian',
                'password'          => bcrypt('Qwe421as1!1'),
                'email_verified_at' => now(),
            ],
        );

        // 3) User dem Tenant zuordnen & Rollen setzen
        $admin->tenant_id = $tenant->id;
        $admin->save();

        $admin->syncRoles(['admin', 'super_admin']);
    }
}
