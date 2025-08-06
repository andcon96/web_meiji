<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDetailUserSeenBy extends Model
{
    use HasFactory;

    public $table = 'receipt_det_user_preview';
}
