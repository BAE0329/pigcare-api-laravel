<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pig_breed extends Model
{
    use HasFactory;

    protected $table='pig_breed';

    protected $fillable = [
        'pig_breed',
        'breed_info',
        'breed_char',
    ];
}