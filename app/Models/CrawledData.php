<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CrawledData extends Model
{
    use SoftDeletes;

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
        'raw_payload',
        'ai_sentiment',
        'main_topic',
        'is_emergency',
        'location',
        'is_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'posted_at'   => 'datetime',
        'is_emergency' => 'boolean',
        'is_validated' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
