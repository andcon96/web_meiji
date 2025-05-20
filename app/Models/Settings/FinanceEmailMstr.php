<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceEmailMstr extends Model
{
    use HasFactory;

    protected $table = 'finance_emails_header';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getFinanceEmails()
    {
        return $this->hasMany(FinanceEmailDet::class, 'fem_id', 'id');
    }
}
