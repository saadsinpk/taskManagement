<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chats extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'message', 'user_chat', 'is_read'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
