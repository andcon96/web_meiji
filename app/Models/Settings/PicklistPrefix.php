<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PicklistPrefix extends Model
{
    use HasFactory;

    protected $table = 'prefix_work_orders';
}
