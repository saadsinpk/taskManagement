<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $table = 'history';

    protected $fillable = [
        'user_id',
        'task_id',
        'old_data',
        'new_data',
        'colume_name',
    ];
}
