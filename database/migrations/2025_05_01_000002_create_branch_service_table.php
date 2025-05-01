<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
    public function up():void{
        Schema::create('branch_service',function(Blueprint $t){
            $t->char('branch_id',36);
            $t->char('service_id',36);
            $t->primary(['branch_id','service_id']);
            $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $t->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });
    }
    public function down():void{Schema::dropIfExists('branch_service');}
};
