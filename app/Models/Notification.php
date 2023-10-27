<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'notifiable_id',
        'notifiable_type',
        'data',
        'read_at',
        'user_id',
    ];
}
