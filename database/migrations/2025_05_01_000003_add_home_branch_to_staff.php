<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
    public function up():void{
        Schema::table('staff',function(Blueprint $t){
            $t->char('home_branch_id',36)->nullable()->after('active');
            $t->foreign('home_branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }
    public function down():void{
        Schema::table('staff',function(Blueprint $t){
            $t->dropForeign(['home_branch_id']);
            $t->dropColumn('home_branch_id');
        });
    }
};
