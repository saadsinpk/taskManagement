<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectOverview extends Model
{
    use HasFactory;

    protected $table = 'projects_overview';

    protected $fillable = [
        'name'
    ];
}
