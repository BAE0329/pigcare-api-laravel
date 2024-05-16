<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pig_heat extends Model
{
    use HasFactory;

    protected $table='pig_heat';

    protected $fillable = [
        'first_heat_date',
        'next_heat_date',
    ];
}