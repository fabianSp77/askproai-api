<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\{Branch,Service,Staff};

class DemoDataSeeder extends Seeder{
    public function run():void{
        DB::transaction(function(){
            $branchA=Branch::firstOrCreate(['slug'=>'downtown'],
                ['id'=>Str::uuid(),'name'=>'Downtown Studio','city'=>'Musterstadt','phone_number'=>'01234 1','active'=>1]);
            $branchB=Branch::firstOrCreate(['slug'=>'uptown'  ],
                ['id'=>Str::uuid(),'name'=>'Uptown Studio'  ,'city'=>'Musterstadt','phone_number'=>'01234 2','active'=>1]);

            $servicePT=Service::firstOrCreate(['name'=>'Personal Training'],
                ['id'=>Str::uuid(),'description'=>'1-zu-1 Training','price'=>79,'active'=>1]);
            $serviceMS=Service::firstOrCreate(['name'=>'Massage 30 min'],
                ['id'=>Str::uuid(),'description'=>'Entspannungsmassage','price'=>39,'active'=>1]);

            $staffAnna=Staff::firstOrCreate(['email'=>'anna@example.com'],
                ['id'=>Str::uuid(),'name'=>'Anna Trainer','phone'=>'0170 1','active'=>1,'home_branch_id'=>$branchA->id]);
            $staffBen =Staff::firstOrCreate(['email'=>'ben@example.com' ],
                ['id'=>Str::uuid(),'name'=>'Ben Masseur','phone'=>'0170 2','active'=>1,'home_branch_id'=>$branchB->id]);

            $branchA->services()->sync([$servicePT->id,$serviceMS->id]);
            $branchB->services()->sync([$serviceMS->id]);

            $branchA->staff()->sync([$staffAnna->id,$staffBen->id]);
            $branchB->staff()->sync([$staffBen->id]);

            $staffAnna->services()->sync([$servicePT->id]);
            $staffBen ->services()->sync([$serviceMS->id]);
        });
    }
}
