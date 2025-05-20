<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getLoadedBy()
    {
        return $this->belongsTo(User::class, 'load_by_id', 'id');
    }
}
