<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FAQ extends Model
{
    protected $table = 'faqs';
    
    protected $fillable = ['question', 'answer', 'active'];
    
    protected $casts = [
        'active' => 'boolean',
    ];
}
