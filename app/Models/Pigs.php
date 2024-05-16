<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pigs extends Model
{
    use HasFactory;

    protected $table='pigs';

    protected $fillable = [
        'pigs_id',
        'user_id',
        'pig_breed',
        'pig_name',
        'weight',
        'gender',
        'pig_stage',
        'date_of_birth',
        'date_of_entry',
        'pig_group',
        'pig_obtained',
        'tag_number',
        'litter_nunber',
        'mothers_tag',
        'fathers_tag',
        'owner_of_pigs',
        'pig_status',
        'status_date',
        'pig_status,'
    ];
}