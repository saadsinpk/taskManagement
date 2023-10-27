<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaskManagement;

class OrderManagement extends Model
{
    use HasFactory;

    protected $table = 'order_management';

    protected $fillable = [
        'order_name',
        'order_status',
        'created_by',
        'date_created',
        'overview',
    ];
}
