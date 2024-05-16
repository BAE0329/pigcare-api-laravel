<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForgotPassCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'expiration',
    ];
}