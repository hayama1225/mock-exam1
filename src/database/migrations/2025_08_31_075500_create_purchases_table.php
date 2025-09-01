<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount'); // JPY
            $table->enum('payment_method', ['card', 'konbini']);
            // 送付先スナップショット（購入時点）
            $table->string('zip', 8);
            $table->string('address');
            $table->string('building')->nullable();
            // Stripe関連（未設定時はnull）
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
