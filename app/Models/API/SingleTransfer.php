<?php

namespace App\Models\API;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class singleTransfer extends Model
{
    use HasFactory;

    public $table = 'single_transfer';

    // public function getUser()
    // {
    //     return $this->belongsTo(User::class, 'ts_created_by', 'id');
    // }
}
