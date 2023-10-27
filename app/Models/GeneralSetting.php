<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $table = 'general_setting';

    protected $fillable = [
        'user_id',
        'title',
        'favicon',
        'street_address',
        'city',
        'state',
        'country',
        'postal_code',
        'footer_text',
        'footer_logo',
    ];
}
