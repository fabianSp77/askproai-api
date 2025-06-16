<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CallsV2Seeder extends Seeder
{
    public function run(): void
    {
        DB::table('calls')->update(['duration_sec' => 0]);   // Platzhalter
    }
}
