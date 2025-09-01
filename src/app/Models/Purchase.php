<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'buyer_id',
        'amount',
        'payment_method',
        'zip',
        'address',
        'building',
        'stripe_session_id',
        'stripe_payment_intent',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
