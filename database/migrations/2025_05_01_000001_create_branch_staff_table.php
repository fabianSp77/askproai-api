<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
    public function up():void{
        Schema::create('branch_staff',function(Blueprint $t){
            $t->char('branch_id',36);
            $t->char('staff_id',36);
            $t->primary(['branch_id','staff_id']);
            $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $t->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
        });
    }
    public function down():void{Schema::dropIfExists('branch_staff');}
};
