<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    use HasFactory;
    protected $table = 'task_activity';

    protected $fillable = [
        'task_id',
        'user_id',
        'note',
        'checkIn',
        'checkOut',
    ];
}
