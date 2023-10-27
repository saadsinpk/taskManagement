<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notifications_setting';

    protected $fillable = [
        'user_id',
        'emailTask',
        'emailChat',
        'webTask',
        'webChat',
    ];
}
