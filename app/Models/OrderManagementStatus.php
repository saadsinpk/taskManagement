<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderManagementStatus extends Model
{
    use HasFactory;

    protected $table = 'order_management_status';

    protected $fillable = [
        'status_name',
        'background',
        'overview',
    ];
}
