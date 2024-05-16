<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedsInfo extends Model
{
    use HasFactory;

    protected $table='feed_info';

    protected $fillable = [
        'feed_type',
        'feed_information',
        'feed_price',
    ];
}