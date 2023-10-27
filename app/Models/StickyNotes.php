<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StickyNotes extends Model
{
    use HasFactory;

    protected $table = 'sticky_notes';

    protected $fillable = [
        'user_id',
        'notes',
        'color',
    ];
}
