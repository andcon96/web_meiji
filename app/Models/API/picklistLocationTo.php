<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class picklistLocationTo extends Model
{
    use HasFactory;
         protected $fillable = [
        'picklist_number',
    ];

    public $table = 'picklist_next_loc';
}
