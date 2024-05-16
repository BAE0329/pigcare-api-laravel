<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void
    {
        Schema::create('forgot_pass_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('code');
            $table->timestamps();
            $table->dateTime('expiration');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('forgot_pass_codes');
    }
};