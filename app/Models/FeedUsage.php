<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedUsage extends Model
{
    use HasFactory;

    protected $table='feeds_usage';

    protected $fillable = [
        'user_id',
        'stage',
        'feeds_added',
        'usage',
    ];
}