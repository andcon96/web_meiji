<?php

namespace App\Models\API;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferStock extends Model
{
    use HasFactory;

    public $table = 'transfer_stock';

    public function getUser()
    {
        return $this->belongsTo(User::class, 'ts_created_by', 'id');
    }
}
