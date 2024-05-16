<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmSetup extends Model
{
    use HasFactory;

    protected $table='farm_setup';

    protected $fillable = [
        'user_id',
        'categories',
        'name',
    ];
}