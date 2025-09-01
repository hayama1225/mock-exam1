<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('username', 20)->nullable();   // 初回は空でもOK
            $table->string('zip', 8)->nullable();         // 例: 123-4567
            $table->string('address')->nullable();
            $table->string('building')->nullable();
            $table->string('avatar_path')->nullable();    // storageパス
            $table->timestamp('profile_completed_at')->nullable(); // 完了判定用
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
