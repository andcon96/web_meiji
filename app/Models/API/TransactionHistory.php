<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\User;

class TransactionHistory extends Model
{
    use HasFactory;

    public $table = 'transaction_history';

     public function getUser()
    {
        return $this->hasMany(User::class, 'arh_receipt_det_id', 'id');
    }

}
