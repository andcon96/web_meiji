<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    
    protected $table = 'item_master';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'im_domain_id', 'id');
    }

    public function getLoadedBy()
    {
        return $this->belongsTo(User::class, 'load_by_id', 'id');
    }

    public function getUpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
