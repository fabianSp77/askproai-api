<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'fabian@askproai.de'],
            [
                'name' => 'Fabian (Super-Admin)',
                'password' => Hash::make('Qwe421as1!1'),
            ],
        );

        $user->syncRoles('super_admin');
    }
}
