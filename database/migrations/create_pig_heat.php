<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void
    {
        Schema::create('pig_heat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pigs_id')->contrained('pigs');
            $table->date('first_heat_date');
            $table->date('next_heat_date');
            $table->string('status');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pig_heat');
    }
};