<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class gestation extends Model
{
    use HasFactory;

    protected $table='gestation';

    protected $fillable = [
        'pigs_id',
        'estimated_labor',
    ];
}