<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    use HasFactory;

    protected $table = 'prefix_number';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }
}
