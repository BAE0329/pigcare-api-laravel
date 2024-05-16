<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void
    {
        Schema::create('pig_breed', function (Blueprint $table) {
            $table->id();
            $table->string('pig_breed');
            $table->string('breed_info');
            $table->string('breed_char');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pig_breed');
    }
};