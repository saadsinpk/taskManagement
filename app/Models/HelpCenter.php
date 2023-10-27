<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpCenter extends Model
{
    use HasFactory;

    protected $table = 'help_center';

    protected $fillable = [
        'title',
        'paragraph',
        'created_by',
    ];
}
