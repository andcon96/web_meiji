<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailForPOSOMonthly extends Model
{
    use HasFactory;

    protected $table = 'emails_for_auto_convert_po_so_monthly';
}
