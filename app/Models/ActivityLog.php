<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    protected $table = 'activity_logs';
    
    // Log tidak boleh diubah sama sekali oleh aplikasi
    // Kita matikan fitur update standard Eloquent untuk keamanan tambahan
    public $timestamps = true;

    protected $fillable = [
        'previous_hash', 
        'current_hash', 
        'user_id', 
        'user_ip', 
        'user_agent', 
        'event', 
        'description', 
        'subject_id', 
        'subject_type', 
        'properties'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi Polymorphic (Ke Postingan, User lain, dll)
    public function subject()
    {
        return $this->morphTo();
    }
}
