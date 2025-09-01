<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; #メール認証有効化
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
#追加（戻り値型のため）
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
#追加（クラス参照のため）
use App\Models\Item;
use App\Models\Profile;
use App\Models\Comment;

class User extends Authenticatable implements MustVerifyEmail #Fortify導入時にimplements以降追記
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ★ ここからリレーション（型付き）
    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\Item::class, 'seller_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(\App\Models\Purchase::class, 'buyer_id');
    }

    public function profile()
    {
        return $this->hasOne(\App\Models\Profile::class);
    }

    public function likedItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'likes')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
