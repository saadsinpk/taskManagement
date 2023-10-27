<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskManagementStatus extends Model
{
    use HasFactory;

    protected $table = 'task_management_status';

    protected $fillable = [
        'status_name',
        'background',
        'overview',
    ];
}
