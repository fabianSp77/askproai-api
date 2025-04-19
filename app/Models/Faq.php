<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        "kunde_id",
        "question",
        "answer",
        "category",
        "active",
        "position"
    ];

    protected $casts = [
        "active" => "boolean",
    ];

    public function kunde(): BelongsTo
    {
        return $this->belongsTo(Kunde::class);
    }
}