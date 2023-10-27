<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToDoListPrivate extends Model
{
    use HasFactory;

    protected $table = 'todo_list_private';

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];
}
