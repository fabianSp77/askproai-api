<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model,Relations\BelongsToMany,Concerns\HasUuids};

class Service extends Model{
    use HasUuids;
    public $incrementing=false; protected $keyType='string';
    protected $fillable=['name','description','price','active'];

    public function branches():BelongsToMany{
        return $this->belongsToMany(Branch::class,'branch_service');
    }
    public function staff():BelongsToMany{
        return $this->belongsToMany(Staff::class,'staff_service')->withTimestamps();
    }
}
