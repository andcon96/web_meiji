<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceEmailDet extends Model
{
    use HasFactory;

    protected $table = 'finance_emails_detail';

    public function getFinanceEmailHeader()
    {
        return $this->belongsTo(FinanceEmailMstr::class, 'fem_id', 'id');
    }
}
