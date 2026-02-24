<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CrawledData extends Model
{
    use SoftDeletes;

    // Set agar ID menggunakan UUID, bukan Auto-Increment
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'previous_hash', 
        'current_hash', 
        'type', 
        'source', 
        'username', 
        'posted_at', 
        'content', 
        'url', 
        'parent_url', 
        'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Otomatis generate UUID saat model dibuat
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
