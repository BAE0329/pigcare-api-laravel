<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PigsInfo extends Model
{
    use HasFactory;

    protected $table='pig_info';

    protected $fillable = [
        'Pig_breed',
        'Pig_Info',
        'Pig_Char',
    ];
}