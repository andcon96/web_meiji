<?php

namespace App\Models\API;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class penyerahanBarang extends Model
{
    use HasFactory;

    public $table = 'penyerahan_barang';

    // public function getUser()
    // {
    //     return $this->belongsTo(User::class, 'ts_created_by', 'id');
    // }
}
