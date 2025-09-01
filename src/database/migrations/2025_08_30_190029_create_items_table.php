<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price'); // 円
            $table->string('condition', 30);  // プルダウン値（後述）
            $table->string('image_path')->nullable(); // storage パス
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id']);
            $table->index(['buyer_id']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
