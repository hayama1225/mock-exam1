<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'zip',
        'address',
        'building',
        'avatar_path',
        'profile_completed_at',
    ];

    protected $casts = [
        'profile_completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 便利アクセサ
    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->profile_completed_at);
    }
}
