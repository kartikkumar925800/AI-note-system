<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'embedding',
        'summary',
        'summary_model',
        'summary_generated_at',
        'content_hash'
    ];

    protected $casts = [
        'summary_generated_at' => 'datetime',
    ];
}
