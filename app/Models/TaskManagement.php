<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskManagement extends Model
{
    use HasFactory;
    protected $table = 'task_management';

    protected $fillable = [
        'title',
        'order_id',
        'assigned_to',
        'priority',
        'status',
        'deadline',
        'description',
        'created_by',
        'start_date',
        'overview',
    ];
}
