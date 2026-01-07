<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileApk extends Model
{
    use HasFactory;

    public $table = 'mobile_apk_hist';

    protected $fillable = [
        'apk_updated_number',
        'apk_url',
        'apk_version',
        'apk_release_notes',
        'apk_is_active',
        'apk_updated_by',
        // kolom lain yang boleh diisi mass-assignment
    ];

    public function getDownloadUrlAttribute()
    {
        return url('/storage/apk/' . $this->apk_url);
    }
}