<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'name',
        'brand',
        'description',
        'price',
        'condition',
        'image_path',
        'buyer_id',
        'sold_at'
    ];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    #選択肢（要件に合わせる）
    public const CONDITIONS = [
        '新品',
        '未使用に近い',
        '目立った傷や汚れなし',
        'やや傷や汚れあり',
        '状態が悪い',
        '良好',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function getIsSoldAttribute(): bool
    {
        return !is_null($this->sold_at) || !is_null($this->buyer_id);
    }
}
